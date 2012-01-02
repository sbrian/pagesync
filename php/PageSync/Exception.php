<?php

class PageSync_Exception extends Exception
{
	public static $delegateClass = NULL;
	
	const LEVEL_WARN = 1;
	const LEVEL_ERROR = 2;
	const LEVEL_FATAL = 3;

	protected $debugInfo;
	protected $codeString;
	protected $level;

	function __construct($message, $code = 0, $debugInfo = NULL,
			$level = self::LEVEL_WARN)
	{
		if ( is_numeric($code) )
		{
			parent::__construct($message, $code);
		}
		else
		{
			parent::__construct($message);
			$this->codeString = $code;
		}
		$this->debugInfo = $debugInfo;
		$this->level = $level;
	}

	public function getCodeString()
	{
		return $this->codeString;
	}
	
	public function getDebugInfo()
	{
		return $this->debugInfo;
	}

	public static function throwException($message, $code, $debugInfo,
		$level = PageSync_Exception::LEVEL_WARN)
	{
		throw new PageSync_Exception($message, $code, $debugInfo, $level);
	}
	
	public static function throwFatal($debugInfo, $message = "Unexpected error")
	{
		self::checkDelegate("throwFatal", $debugInfo, $message);
		throw new PageSync_Exception($message, 0, $debugInfo, self::LEVEL_FATAL);
	}
	
	public static function throwError($debugInfo, $message = "Unexpected error")
	{
		self::checkDelegate("throwError", $debugInfo, $message);
		throw new PageSync_Exception($message, 0, $debugInfo, self::LEVEL_ERROR);
	}
	
	public static function throwWarn($debugInfo, $message = "Unexpected error")
	{
		self::checkDelegate("throwWarn", $debugInfo, $message);
		throw new PageSync_Exception($message, 0, $debugInfo, self::LEVEL_WARN);
	}
	
	private function checkDelegate($methodName, $debugInfo, $message)
	{
		if ( self::$delegateClass )
			call_user_func(array(self::$delegateClass, $methodName), $debugInfo, $message);
	}
}
