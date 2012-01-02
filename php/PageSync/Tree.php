<?php

require_once("PageSync/Exception.php");

/**
 *  @author Garfinkel Smith <asdf@fdsa.com>
 */
class PageSync_Tree
{	
	const INFO_TYPE = "type";
	const INFO_NAME = "name";
	const INFO_CHILDREN = "children";
	const INFO_VALUE = "value";
	const INFO_ID = "id";
	const INFO_PARENT = "parent";
	const INFO_DEPTH = "depth";
	
	const TYPE_ELEMENT = "element";
	const TYPE_TEXT = "text";
	const TYPE_ATTRIBUTE = "attribute";
	
	private $tree;
	private $childrenStack;
	private $inText;
	private static $id = 1;

	private $depth;
	private $currentNodeStack;
	public $ignoreText = FALSE;
	public $ignoreWhitespace = TRUE;
	
	public static function &parse($document, $ignoreText = FALSE)
	{
		$parser = new PageSync_Tree();
		$parser->ignoreText = $ignoreText;
		return $parser->_parse($document);	
	}
	
	public static function &appendChild(&$node, $newNode)
	{
		$newNode[self::INFO_ID] = self::$id++;
		$newNode[self::INFO_PARENT] =& $node;
		$node[self::INFO_CHILDREN][] =& $newNode;
		return $newNode;
	}
	
	public static function deleteNode(&$node)
	{
		$parent =& $node[self::INFO_PARENT];
		if ( ! $parent )
			PageSync_Exception::throwFatal("Cannot delete node with no parent");
		$c = count($parent[self::INFO_CHILDREN]);
		for( $i=0; $i<$c; $i++ )
		{
			if ( $node[self::INFO_ID] ===
				$parent[self::INFO_CHILDREN][$i][self::INFO_ID] )
			{
				array_splice($parent[self::INFO_CHILDREN], $i, 1);
				return;
			}
		}
		PageSync_Exception::throwFatal("Corrupted tree, node is not a child of its parent");
	}
	
	public static function &prependSibling(&$node, $newNode)
	{
		$parent =& $node[self::INFO_PARENT];
		if ( ! $parent ) PageSync_Exception::throwFatal("Node has no parent");
		$siblings =& $parent[self::INFO_CHILDREN];
		if ( ! $siblings ) PageSync_Exception::throwFatal("No siblings");
		$x=0;
		while($siblings[$x][self::INFO_ID]!=$node[self::INFO_ID]) $x++;
		$newNode[self::INFO_ID] = self::$id++;
		unset($newNode[self::INFO_PARENT]);
		$newNode[self::INFO_PARENT] =& $parent;
		$insertion = array();
		$insertion[] =& $newNode;
		array_splice($siblings, $x, 0, $insertion);
		return $newNode;
	}
	
	/**
	 * Takes a recursively constructed tree and places each value in an
	 * array ordered by unique ID.
	 */
	public static function toArray(&$tree, &$array)
	{
		if ( ! is_array($tree) ) PageSync_Exception::throwFatal("Tree is not an array");
		$array[] =& $tree;
		if ( $tree[self::INFO_TYPE] != self::TYPE_ELEMENT ) return;
		foreach( $tree[self::INFO_CHILDREN] as &$child )
		{
			self::toArray($child, $array);
			// Some linkages are getting lost if this is not done.
			$child[self::INFO_PARENT] =& $tree;
		}
	}
	
	public static function &findInArrayByID(&$array, $id)
	{
		$c = count($array);
		for($i=0; $i<$c; $i++)
		{
			if ( $array[$i][self::INFO_ID] === $id )
				return $array[$i];
		}
		return NULL;
	} 
	
	/**
	 * Takes a recursively constructed tree and places each value in an
	 * array ordered by unique ID.
	 */
	public static function &findByID(&$tree, $id)
	{
		if ( ! is_array($tree) ) PageSync_Exception::throwFatal("Tree is not an array");
		if ( $tree[self::INFO_ID] == $id ) return $tree;
		if ( $tree[self::INFO_TYPE] != self::TYPE_ELEMENT ) return NULL;
		foreach( $tree[self::INFO_CHILDREN] as &$child )
		{
			$node =& self::findByID($child, $id);
			if ( $node ) return $node;
		}
		return NULL;
	}
	
	public static function removeArrayElement(&$array, $pos)
	{
		array_splice($array, $pos, 1);
	}
	
	public static function moveArrayElement(&$array, $pos, $newPos)
	{
		if( $pos == $newPos ) PageSync_Exception::throwFatal("Positions cannot be equal");
		$removed = array_splice($array, $pos, 1);
		if ( count($removed) != 1 )
			throw PageSync_Exception::throwFatal("Unexpected number removed: " . count($removed));
		if ( $newPos <= $pos )
			$removed[] = $array[$newPos];
		array_splice($array, $newPos, 1, $removed);
	}
	
	public static function getXpathName($node)
	{
		if ( $node[self::INFO_TYPE] == self::TYPE_TEXT )
			return "text()";
		return $node[self::INFO_NAME];
	}
	
	public static function getXpathNumber($node)
	{
		if ( ! isset($node[self::INFO_PARENT]) )
		{
			throw PageSync_Exception::throwFatal("Can't get node number of a parentless node");
		}
		$siblings = $node[self::INFO_PARENT][self::INFO_CHILDREN];
		$c = 1;
		foreach($siblings as $sibling)
		{
			if ( $node[self::INFO_ID] == $sibling[self::INFO_ID] )
			{
				return $c;
			}
			else if ( self::getXpathName($node) == self::getXpathName($sibling) )
			{
				$c++;
			}
		}
		throw PageSync_Exception::throwFatal("Malformed node tree, node was not a child of its parent: "
			. $node[self::INFO_NAME]);
	}
	
	public static function getXpath($node)
	{
		if ( ! $node || ! is_array($node) )
			throw PageSync_Exception::throwFatal("Node must be an array");
		$pathString = "/" . self::getXpathName($node);
		if ( $node[self::INFO_PARENT] != null )
		{
			$pathString = self::getXpath($node[self::INFO_PARENT]) . $pathString
				. "[" . self::getXpathNumber($node) . "]";	
		}
		return $pathString;
	}
	
	public static function toString($node, $indent = "")
	{
		$str =
		$indent . "ID=" . $node[self::INFO_ID] . "\n" .
		$indent . "TYPE=" . $node[self::INFO_TYPE] . "\n" .
		$indent . "NAME=" . $node[self::INFO_NAME] . "\n" .
		$indent . "XPATH=" . self::getXpath($node) . "\n";
		$indent . "DEPTH=" . $node[self::INFO_DEPTH] . "\n";
		if ( isset($node[self::INFO_PARENT]) )
			$str .= $indent . "PARENTID=" . $node[self::INFO_PARENT][self::INFO_ID] . "\n";
		if ( $node[self::INFO_TYPE] == self::TYPE_TEXT ||
			$node[self::INFO_TYPE] == self::TYPE_ATTRIBUTE )
		{
			return $str . $indent . "VALUE=" . $node[self::INFO_VALUE] . "\n";
		}
		foreach($node[self::INFO_CHILDREN] as $child)
		{
			$str .= self::toString($child, $indent . "  ");	
		}
		return $str;
	}
	
	public static function describeArrayOfTrees($ary, $showEntire = FALSE)
	{
		$result = "";
		foreach($ary as $tree)
		{
				$result .= $tree[self::INFO_ID] . "(" . $tree[self::INFO_TYPE];
				if ( $tree[self::INFO_TYPE] == self::TYPE_ELEMENT )
				{
					$result .= " <" . $tree[self::INFO_NAME] . ">";	
				}
				$result .= ")\n";
				if ( $showEntire )
				{
					$result .= self::toString($tree) . "\n";	
				}
		}
		return $result;
	}

	public static function detachNode($node)
	{
		$newNode = array();
		$newNode[self::INFO_TYPE] = $node[self::INFO_TYPE];
		if ( $node[self::INFO_TYPE] == self::TYPE_ELEMENT )
		{
			$newNode[self::INFO_CHILDREN] = array();
			foreach($node[self::INFO_CHILDREN] as $child)
			{
				$newNode[self::INFO_CHILDREN][] = self::detachNode($child);
			}
		}
		if ( isset($node[self::INFO_NAME]) )
			$newNode[self::INFO_NAME] = $node[self::INFO_NAME];
		if ( isset($node[self::INFO_VALUE]) )
			$newNode[self::INFO_VALUE] = $node[self::INFO_VALUE];
		return $newNode;	
	}
	
	public static function testParentReferences(&$node)
	{
		if ( $node[self::INFO_TYPE] != self::TYPE_ELEMENT )
			return;
		foreach( $node[self::INFO_CHILDREN] as &$child )
		{
			self::testParentReferences($child);
		}
		echo "NODE: " . $node[self::INFO_NAME] . "\n";
		if ( ! $node[self::INFO_PARENT] ) return;
		$n1 = count($node[self::INFO_PARENT][self::INFO_CHILDREN]);
		$n2 = count($node[self::INFO_PARENT][self::INFO_CHILDREN][0][self::INFO_PARENT][self::INFO_CHILDREN]);
		echo "$n1 == $n2\n";
		if ( $n1 != $n2 ) throw PageSync_Exception::throwFatal("Fatal error");
		$node[self::INFO_PARENT][self::INFO_CHILDREN][] = "testing";
		$n3 = count($node[self::INFO_PARENT][self::INFO_CHILDREN]);
		$n4 = count($node[self::INFO_PARENT][self::INFO_CHILDREN][0][self::INFO_PARENT][self::INFO_CHILDREN]);
		if ( $n3 != $n4 ) throw PageSync_Exception::throwFatal("Fatal error");
	}
	
	private function &_parse($document)
	{
		$this->depth = 0;
		$this->tree = NULL;
		$this->childrenStack = array();
		$this->inText = false;
		$parser = xml_parser_create();
		xml_set_element_handler($parser, array($this,
			"startElement"), array($this, "endElement"));
		xml_set_character_data_handler($parser,
			array($this, "characterData"));
		$parse = xml_parse($parser, $document);
		if ( $parse !== 1 )
		{
			$error = xml_error_string(xml_get_error_code($parser));
		}
		xml_parser_free($parser);
		if ( $error )
		{
			error_log($document);
			throw PageSync_Exception::throwFatal("Parser error: $error");
		}
		return $this->tree;
	}	

	private function startElement($parser, $type, $attributes)
	{
		if ( $this->inText ) $this->closeText();
		$element
			= array(
			self::INFO_TYPE => self::TYPE_ELEMENT,
			# XPATH in Ajaxslt expects UC element names
			self::INFO_NAME => strtoupper($type),
			self::INFO_CHILDREN => array(),
			self::INFO_ID => self::$id++,
			self::INFO_DEPTH => $this->depth++
		);
		
		ksort($attributes);
		foreach($attributes as $k=>$v)
		{
			$newAttribute = array(
				self::INFO_TYPE => self::TYPE_ATTRIBUTE,
				# Interet Explorer 7 expects LC attribute names
				self::INFO_NAME => strtolower($k),
				self::INFO_VALUE => $v,
				self::INFO_ID => self::$id++,
				self::INFO_DEPTH => $this->depth
			);
			$newAttribute[self::INFO_PARENT] =& $element;
			$element[self::INFO_CHILDREN][] =& $newAttribute;
			unset($newAttribute);
		}
		
		if ( $this->tree == NULL )
		{
			$this->tree =& $element;
		}
		else
		{
			$element[self::INFO_PARENT] =& $this->currentNodeStack[count($this->currentNodeStack)-1];
			$this->currentChildren[] =& $element;
			$this->childrenStack[] =& $this->currentChildren;
			unset($this->currentChildren);
		}
		$this->currentNodeStack[] =& $element;
		
		$this->currentChildren =& $element[self::INFO_CHILDREN];
	}

	private function endElement($parser, $type)
	{
		if ( $this->inText ) $this->closeText();
		unset($this->currentChildren);
		$lastIndex = count($this->childrenStack)-1;
		if ( $lastIndex >= 0 )
		{
			$this->currentChildren =&
			$this->childrenStack[$lastIndex];
			array_pop($this->childrenStack);
		}
		array_pop($this->currentNodeStack);
		$this->depth--;
	}
	
	private function characterData($parser, $data)
	{
		if ( $this->ignoreText ) return;
		if ( $this->inText )
		{
			$this->currentChildren[count($this->currentChildren) - 1]
				[self::INFO_VALUE]
				.= $data;
			return;
		}
		$this->inText = TRUE;
		$this->currentChildren[] =
		array(
			self::INFO_TYPE => self::TYPE_TEXT,
			self::INFO_VALUE => $data,
			self::INFO_ID => self::$id++,
			self::INFO_PARENT => $this->currentNodeStack[count($this->currentNodeStack)-1],
			self::INFO_DEPTH => $this->depth);
	}
	
	private function closeText()
	{
		$this->inText = FALSE;
		if ( $this->ignoreWhitespace &&
			preg_match("/^\s+$/", $this->currentChildren[count($this->currentChildren) - 1]
				[self::INFO_VALUE]) )
		{
			array_pop($this->currentChildren);
			self::$id--;
		}
	}
	
	
}

?>
