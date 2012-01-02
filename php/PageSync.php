<?php

require_once("PageSync/ScriptBuilder.php");

class PageSync
{ 
	const RESPONSE_SUCCESS = "Success";
	
	const RESPONSE_NO_ORIGINAL = "No Original";
	
	const RESPONSE_ERROR = "Error";
	
	public function doNoOriginal($newUrl = NULL)
	{
		if ( ! $newUrl )
		{
			$newUrl = self::buildNewUrl();
		}
		return json_encode(array(self::RESPONSE_NO_ORIGINAL,
			$newUrl));
	}
	
	public function doError($message)
	{
		return json_encode(array(self::RESPONSE_ERROR, $message));
	}
	
	public function doProcessing($originalHtml, $newHtml)
	{
		try
		{
			error_log("START PROC");
			$scriptBuilder = new PageSync_ScriptBuilder();
			$script = $scriptBuilder->processTrees($originalHtml, $newHtml);
			array_unshift($script, self::RESPONSE_SUCCESS);
			return json_encode($script);
		}
		catch(Exception $e)
		{
			error_log("Exception: " . $e->getTraceAsString());
			return self::doError($e->getMessage());
		}
	}
	
	public function buildNewUrl()
	{
		$get = "";
		foreach($_GET as $k=>$v)
		{
			if ( $k == "format" ) continue;
			if ( $k == "u" ) continue;
			if ( $get ) $get .= "&";
			$get .= urlencode($k) . "=" . urlencode($v);
		}
		return $_SERVER["SCRIPT_NAME"] . "?" . $get;
	}
}