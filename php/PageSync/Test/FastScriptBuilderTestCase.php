<?php

require_once("PHPUnit/Framework/TestCase.php");
require_once("PageSync/ScriptBuilder.php");
require_once("PageSync/Test/ScriptBuilderTestCase.php");

class PageSync_Test_FastScriptBuilderTestCase extends PageSync_Test_ScriptBuilderTestCase
{
	protected function getMode()
	{
		return PageSync_ScriptBuilder::FAST;
	}
	
	public function test28()
	{
		$test1 = '<root><a/><b/><c/><a/><b/><c/></root>';
		$test2 = '<root><b/><c/><a/><b/><c/></root>';
		if ( $this->getMode() == PageSync_ScriptBuilder::ACCURATE )
		{
			$result = array(
				array(
					PageSync_ScriptBuilder::DELETE,
					"/ROOT/A[1]",
				)
			);
		}
		else
		{
			$result = array(
				array(
					PageSync_ScriptBuilder::APPEND_COPY_AS_CHILD,
					"/ROOT/A[1]",
					"/ROOT"
				),
				array(
					PageSync_ScriptBuilder::APPEND_COPY_AS_CHILD,
					"/ROOT/B[1]",
					"/ROOT"
				),
				array(
					PageSync_ScriptBuilder::APPEND_COPY_AS_CHILD,
					"/ROOT/C[1]",
					"/ROOT"
				),
				array(
					PageSync_ScriptBuilder::DELETE,
					"/ROOT/A[1]"
				)
			);	
		}
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
}