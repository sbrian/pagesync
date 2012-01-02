<?php

require_once("PHPUnit/Framework/TestCase.php");
require_once("PageSync/ScriptBuilder.php");
require_once("PageSync/Test/ScriptBuilderTestCase.php");

class PageSync_Test_AccurateScriptBuilderTestCase extends PageSync_Test_ScriptBuilderTestCase
{
	protected function getMode()
	{
		return PageSync_ScriptBuilder::ACCURATE;
	}
}