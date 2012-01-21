PageSync = function(scope, myDocument)
{
	var RESPONSE_SUCCESS = "Success";
	var RESPONSE_NO_ORIGINAL = "No Original";
	var RESPONSE_ERROR = "Error";
	
	var INFO_TYPE = "type";
	var INFO_NAME = "name";
	var INFO_CHILDREN = "children";
	var INFO_VALUE = "value";
	var INFO_ID = "id";
	var INFO_PARENT = "parent";
	var INFO_DEPTH = "depth";
	
	var TYPE_ELEMENT = "element";
	var TYPE_TEXT = "text";
	var TYPE_ATTRIBUTE = "attribute";
	
	if ( ! scope ) scope = window.document;
	
	var scopeIsGlobal = ( scope == window.document );
	
	var errorHandlerFunction = null;
	var noOriginalHandlerFunction = null;
	
	this.doServerAction = function(action, params)
	{
		var url = action;
		if ( url.indexOf('?') == -1 )
			url += "?u=" + Math.random();
		else
			url += "&u=" + Math.random();
		if ( params )
		{
			for (var i = 0; i < this.params.length; i++)
			{
				url += "&param[]=" + this.params[i];
			}
		}
		
		jQuery.ajax(
		{
			type: 'GET',
			url: url,
			success: this.wrapFunction(this.ajaxSuccessHandler),
			error: this.wrapFunction(this.ajaxFailureHandler)
		});
	};
	
	this.ajaxSuccessHandler = function(o)
	{
		var responseObj;
		eval("responseObj = " + o + ";");
		if ( responseObj.constructor != Array )
		{
			throw "Response from server is not an array";
		}
		this.processScript(responseObj);
	};
	
	this.ajaxFailureHandler = function(xmlHttRequest, textStatus, errorThrown)
	{
		alert("here");
		throw "Ajax operation failed: " + textStatus;
	};
	
	this.processScript = function(script)
	{
		if ( script[0] == RESPONSE_ERROR )
		{
			throw script[1];
		}
		if ( script[0] == RESPONSE_NO_ORIGINAL )
		{
			if ( noOriginalHandlerFunction )
			{
				noOriginalHandlerFunction(script[1]);
				return;
			}
			if ( scopeIsGlobal )
			{
				scope.location.href = script[1];
				return;
			}
			else if ( myDocument )
			{
				myDocument.location.href = script[1];
				return;
			}
			throw "Unable to update against missing original";
		}
		if ( script[0] != RESPONSE_SUCCESS )
		{
			throw "Unknown response: " + script[0];
		}
		for( var i=1;i<script.length;i++ )
		{
			this.processScriptLine(script[i]);
		}
	};

	this.processScriptLine = function(scriptLine)
	{
		switch(scriptLine[0])
		{
			case 1:
				this.deleteNode(scriptLine[1]);
				break;
			case 2:
				this.appendCopyAsChild(scriptLine[1], scriptLine[2]);
				break;
			case 3:
				this.insertCopyBefore(scriptLine[1], scriptLine[2]);
				break;
			case 4:
				this.appendChildNode(scriptLine[1], scriptLine[2]);
				break;
			case 5:
				this.insertNodeBefore(scriptLine[1], scriptLine[2]);
				break;
			case 6:
				this.setTextValue(scriptLine[1], scriptLine[2]);
				break;
			case 7:
				this.setAttribute(scriptLine[1], scriptLine[2], scriptLine[3]);
				break;
			default:
				throw "Unknown operation: " + scriptLine[0];
		}
	};
	
	this.insertCopyBefore = function(sourceXpath, destXpath)
	{
		var sourceNode = this.getByXpath(sourceXpath);
		var destNode = this.getByXpath(destXpath);
		destNode.parentNode.insertBefore(sourceNode, destNode);
	};
	
	this.appendCopyAsChild = function(sourceXpath, destXpath)
	{
		var sourceNode = this.getByXpath(sourceXpath);
		var destNode = this.getByXpath(destXpath);
		destNode.appendChild(sourceNode);	
	};
	
	this.deleteNode = function(xpath)
	{
		var node = this.getByXpath(xpath);
		node.parentNode.removeChild(node);
	};
	
	this.insertNodeBefore = function(xpath, nodeDesc)
	{
		var destNode = this.getByXpath(xpath);
		destNode.parentNode.insertBefore(this.buildNode(nodeDesc), destNode);
	};
	
	this.appendChildNode = function(xpath, nodeDesc)
	{
		var destNode = this.getByXpath(xpath);
		if ( ! destNode ) throw "Couldn't get node for: " + xpath;
		destNode.appendChild(this.buildNode(nodeDesc));
	};
	
	this.setTextValue = function(xpath, text)
	{
		var destNode = this.getByXpath(xpath);
		destNode.data = text;
	};
	
	this.setAttribute = function(xpath, name, value)
	{
		var destNode = this.getByXpath(xpath);
		destNode.setAttribute(name, value);
	};
	
	this.getByXpath = function(xpath)
	{
		// TODO: this is broken - reference to top level node becomes "" and
		// error is thrown. -sbs
		if ( ! scopeIsGlobal )
			xpath = xpath.replace(/^\/[^\/]+\/?/, "");
		if ( xpath == "" ) return scope;
		var nodeSet = xpathEval(xpath, new ExprContext(scope));
		if ( ! nodeSet ) throw "Node not found: xpath";
		node = nodeSet.nodeSetValue();
		if ( node.length != 1 )
			throw "Unexpected number of nodes returned: " + xpath + ": " + node.length;
		return node[0];
	};
	
	this.buildNode = function(descArray)
	{
		if ( descArray[INFO_TYPE] == TYPE_TEXT )
		{
			return this.buildTextNode(descArray);
		}
		if ( descArray[INFO_TYPE] == TYPE_ELEMENT )
		{
			return this.buildElementNode(descArray);
		}
		return this.buildElementNode(descArray);
	};
	
	this.buildElementNode = function(descArray)
	{
		var newElement = scope.createElement(descArray[INFO_NAME]);
		if( ! descArray[INFO_CHILDREN] ) return newElement;
		for(var x=0; x<descArray[INFO_CHILDREN].length;x++)
		{
			var child = descArray[INFO_CHILDREN][x];
			if ( child[INFO_TYPE] == TYPE_ATTRIBUTE )
			{
				newElement.setAttribute(child[INFO_NAME], child[INFO_VALUE]);
			}
			else
			{
				var childNode = this.buildNode(child);
				newElement.appendChild(childNode);
			}
		}
		return newElement;
	};
	
	this.buildTextNode = function(descArray)
	{
		return scope.createTextNode(descArray[INFO_VALUE]);
	};
	
	this.wrapFunction = function(fn)
	{
		var savedThis = this;
		return function()
		{
			try
			{
				fn.apply(savedThis, arguments);
			}
			catch(ex)
			{
				//savedThis.log(ex);
				savedThis.handleError(ex);
			}
		}
	};
	
	this.handleError = function(ex)
	{
		if ( ! errorHandlerFunction )
		{
			throw ex;
		}
		errorHandlerFunction(ex);
	};
	
	this.setErrorHandler = function(handlerFunction)
	{
		errorHandlerFunction = handlerFunction;	
	};
};

