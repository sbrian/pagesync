<?php

require_once("PHPUnit/Framework/TestCase.php");
require_once("PageSync/ScriptBuilder.php");

abstract class PageSync_Test_ScriptBuilderTestCase extends PHPUnit_Framework_TestCase
{
	protected $scriptBuilder;
	
	protected abstract function getMode();
	
	protected function setUp()
	{
		$this->scriptBuilder =
			new PageSync_ScriptBuilder($this->getMode());
	}
	
	public function test1()
	{
		$test1 = '<root><a/><b/></root>';
		$test2 = '<root><b/><a/></root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::APPEND_COPY_AS_CHILD,
				"/ROOT/A[1]",
				"/ROOT"),
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test2()
	{
		$test1 = '<root><c/><a/><b/></root>';
		$test2 = '<root><a/><b/><c/></root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::APPEND_COPY_AS_CHILD,
				"/ROOT/C[1]",
				"/ROOT"),
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test3()
	{
		$test1 = '<root><b/><c/><a/></root>';
		$test2 = '<root><a/><b/><c/></root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::INSERT_COPY_BEFORE,
				"/ROOT/A[1]",
				"/ROOT/B[1]"),
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test4()
	{
		$test1 = '<root><c><a/><b/></c></root>';
		$test2 = '<root><c><b/><a/></c></root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::APPEND_COPY_AS_CHILD,
				"/ROOT/C[1]/A[1]",
				"/ROOT/C[1]"),
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);	
		$this->assertEquals($script, $result);
	}
	
	public function test5()
	{
		$test1 = '<root><c/><c><a/><b/></c></root>';
		$test2 = '<root><c/><c><b/><a/></c></root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::APPEND_COPY_AS_CHILD,
				"/ROOT/C[2]/A[1]",
				"/ROOT/C[2]"),
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test6()
	{
		$test1 = '<root><a/><c><a/><b/></c></root>';
		$test2 = '<root><a/><c><b/><a/></c></root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::APPEND_COPY_AS_CHILD,
				"/ROOT/C[1]/A[1]",
				"/ROOT/C[1]"),
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test7()
	{
		$test1 = '<root><a/><c><b/><a/></c></root>';
		$test2 = '<root><c><a/><b/></c></root>';
		$result = array(

			array(
				PageSync_ScriptBuilder::APPEND_COPY_AS_CHILD,
				"/ROOT/C[1]/B[1]",
				"/ROOT/C[1]"),
			array(
				PageSync_ScriptBuilder::DELETE,
				"/ROOT/A[1]")
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test8()
	{
		$test1 = '<root><a/><d><b/></d><c/></root>';
		$test2 = '<root><c/><d><b/></d><a/></root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::INSERT_COPY_BEFORE,
				"/ROOT/A[1]",
				"/ROOT/C[1]"),
			array(
				PageSync_ScriptBuilder::INSERT_COPY_BEFORE,
				"/ROOT/C[1]",
				"/ROOT/D[1]"),
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}

	public function test9()
	{
		$test1 = '<root><a/><d><e/><f/></d><c/></root>';
		$test2 = '<root><c/><d><f/><e/></d><a/></root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::APPEND_COPY_AS_CHILD,
				"/ROOT/D[1]/E[1]",
				"/ROOT/D[1]"),
			array(
				PageSync_ScriptBuilder::INSERT_COPY_BEFORE,
				"/ROOT/A[1]",
				"/ROOT/C[1]"),
			array(
				PageSync_ScriptBuilder::INSERT_COPY_BEFORE,
				"/ROOT/C[1]",
				"/ROOT/D[1]"),
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test10()
	{
		$test1 = '<root><a/><d><a/><c/></d><c/></root>';
		$test2 = '<root><c/><d><c/><a/></d><a/></root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::APPEND_COPY_AS_CHILD,
				"/ROOT/D[1]/A[1]",
				"/ROOT/D[1]"),
			array(
				PageSync_ScriptBuilder::INSERT_COPY_BEFORE,
				"/ROOT/A[1]",
				"/ROOT/C[1]"),
			array(
				PageSync_ScriptBuilder::INSERT_COPY_BEFORE,
				"/ROOT/C[1]",
				"/ROOT/D[1]"),
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test11()
	{
		$test1 = '<root></root>';
		$test2 = '<root>test</root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::APPEND_CHILD_NODE,
				"/ROOT",
				array(
					PageSync_Tree::INFO_TYPE
					=> PageSync_Tree::TYPE_TEXT,
					PageSync_Tree::INFO_VALUE
					=> "test"
				)
			)
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test12()
	{
		$test1 = '<root><a/><a/></root>';
		$test2 = '<root><a/><a/><a><b><c/></b></a></root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::APPEND_CHILD_NODE,
				"/ROOT",
				array(
					PageSync_Tree::INFO_TYPE
					=> PageSync_Tree::TYPE_ELEMENT,
					PageSync_Tree::INFO_NAME
					=> "A",
					PageSync_Tree::INFO_CHILDREN
					=> array(
						array(
							PageSync_Tree::INFO_TYPE
							=> PageSync_Tree::TYPE_ELEMENT,
							PageSync_Tree::INFO_NAME
							=> "B",
							PageSync_Tree::INFO_CHILDREN
							=> array(
								array(
									PageSync_Tree::INFO_TYPE
									=> PageSync_Tree::TYPE_ELEMENT,
									PageSync_Tree::INFO_NAME
									=> "C",
									PageSync_Tree::INFO_CHILDREN
									=> array()
						)),
					)),
				)
			)
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test13()
	{
		$test1 = '<root><a/><b/></root>';
		$test2 = '<root><g/><b/><a/></root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::APPEND_COPY_AS_CHILD,
				"/ROOT/A[1]",
				"/ROOT"),
			array(
				PageSync_ScriptBuilder::INSERT_NODE_BEFORE,
				"/ROOT/B[1]",
				array(
					PageSync_Tree::INFO_TYPE
					=> PageSync_Tree::TYPE_ELEMENT,
					PageSync_Tree::INFO_CHILDREN
					=> array(),
					PageSync_Tree::INFO_NAME
					=> "G"
				)
			)
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test14()
	{
		$test1 = '<root><a/><b/></root>';
		$test2 = '<root><b/><g/><a/></root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::APPEND_COPY_AS_CHILD,
				"/ROOT/A[1]",
				"/ROOT"),
			array(
				PageSync_ScriptBuilder::INSERT_NODE_BEFORE,
				"/ROOT/A[1]",
				array(
					PageSync_Tree::INFO_TYPE
					=> PageSync_Tree::TYPE_ELEMENT,
					PageSync_Tree::INFO_CHILDREN
					=> array(),
					PageSync_Tree::INFO_NAME
					=> "G"
				)
			)
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test15()
	{
		$test1 = '<root><a/><b/><c/></root>';
		$test2 = '<root><b/><c/><g/><a/></root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::APPEND_COPY_AS_CHILD,
				"/ROOT/A[1]",
				"/ROOT"
				),
			array(
				PageSync_ScriptBuilder::INSERT_NODE_BEFORE,
				"/ROOT/A[1]",
				array(
					PageSync_Tree::INFO_TYPE
					=> PageSync_Tree::TYPE_ELEMENT,
					PageSync_Tree::INFO_CHILDREN
					=> array(),
					PageSync_Tree::INFO_NAME
					=> "G"
				)
			),
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test16()
	{
		$test1 = '<root><a>blah</a><a>testy</a><a>something else</a></root>';
		$test2 = '<root><a>testy</a><a>something else</a><d/><a>blah</a></root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::APPEND_COPY_AS_CHILD,
				"/ROOT/A[1]",
				"/ROOT"
				),
			array(
				PageSync_ScriptBuilder::INSERT_NODE_BEFORE,
				"/ROOT/A[3]",
				array(
					PageSync_Tree::INFO_TYPE
					=> PageSync_Tree::TYPE_ELEMENT,
					PageSync_Tree::INFO_CHILDREN
					=> array(),
					PageSync_Tree::INFO_NAME
					=> "D"
					)
				)
			);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test17()
	{
		$test1 = '<root>tes</root>';
		$test2 = '<root>test</root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::SET_TEXT_VALUE,
				"/ROOT/text()[1]",
				"test"
			)
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test18()
	{
		$test1 = '<root><a>test</a></root>';
		$test2 = '<root><a href="someotherurl">test</a></root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::SET_ATTRIBUTE,
				"/ROOT/A[1]",
				"href",
				"someotherurl"
			)
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test19()
	{
		$test1 = '<root><a href="s">test</a></root>';
		$test2 = '<root><a href="someotherurl">test</a></root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::SET_ATTRIBUTE,
				"/ROOT/A[1]",
				"href",
				"someotherurl"
			)
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test20()
	{
		$test1 = '<root><a>tes</a></root>';
		$test2 = '<root><a>test</a></root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::SET_TEXT_VALUE,
				"/ROOT/A[1]/text()[1]",
				"test"
			)
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test21()
	{
		$test1 = file_get_contents(dirname(__FILE__) . "/test21_1.txt");
		$test2 = file_get_contents(dirname(__FILE__) . "/test21_2.txt");
		$result = array (
			  array (
			    0 => 2,
			    1 => '/HTML/BODY[1]/TABLE[1]/TR[3]',
			    2 => '/HTML/BODY[1]/TABLE[1]',
			  ),
			  array (
			    0 => 3,
			    1 => '/HTML/BODY[1]/TABLE[1]/TR[3]',
			    2 => '/HTML/BODY[1]/TABLE[1]/TR[5]',
			  ),
			  array (
			    0 => 3,
			    1 => '/HTML/BODY[1]/TABLE[1]/TR[3]',
			    2 => '/HTML/BODY[1]/TABLE[1]/TR[7]',
			  ),
			  array (
			    0 => 3,
			    1 => '/HTML/BODY[1]/TABLE[1]/TR[3]',
			    2 => '/HTML/BODY[1]/TABLE[1]/TR[7]',
			  ),
		);						
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test23()
	{
		$test1 = file_get_contents(dirname(__FILE__) . "/test23_1.txt");
		$test2 = file_get_contents(dirname(__FILE__) . "/test23_2.txt");
		$result = array (
			  array (
			    0 => 2,
			    1 => '/TBODY/TD[1]',
			    2 => '/TBODY',
			  ),
			  array (
			    0 => 2,
			    1 => '/TBODY/TD[1]',
			    2 => '/TBODY',
			  ),
		);						
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test24()
	{
		$test1 = '<root><a>a</a><a>a</a><b>b</b><b>b</b></root>';
		$test2 = '<root><b>b</b><b>b</b><a>a</a><a>a</a></root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::APPEND_COPY_AS_CHILD,
				"/ROOT/A[1]",
				"/ROOT"),
			array(
				PageSync_ScriptBuilder::APPEND_COPY_AS_CHILD,
				"/ROOT/A[1]",
				"/ROOT"),
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		//print_r($script);
		$this->assertEquals($script, $result);
	}
	
	public function test25()
	{
		$test1 = file_get_contents(dirname(__FILE__) . "/test25_1.txt");
		$test2 = file_get_contents(dirname(__FILE__) . "/test25_2.txt");
		$result = array (
			  array (
			    0 => 2,
			    1 => '/TBODY/TR[1]',
			    2 => '/TBODY',
			  ),
			  array (
			    0 => 2,
			    1 => '/TBODY/TR[2]',
			    2 => '/TBODY',
			  ),
		);						
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test26()
	{
		$test1 = '<root><a>a</a><b>b</b><a>a</a><b>b</b></root>';
		$test2 = '<root><b>b</b><b>b</b><a>a</a><a>a</a></root>';
		$result = array(
			array(
				PageSync_ScriptBuilder::APPEND_COPY_AS_CHILD,
				"/ROOT/A[1]",
				"/ROOT"),
			array(
				PageSync_ScriptBuilder::APPEND_COPY_AS_CHILD,
				"/ROOT/A[1]",
				"/ROOT"),
		);
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		$this->assertEquals($script, $result);
	}
	
	public function test27()
	{
		$test1 = file_get_contents(dirname(__FILE__) . "/test27_1.txt");
		$test2 = file_get_contents(dirname(__FILE__) . "/test27_2.txt");
		$result = array (
			// Need to put something here
		);					
		$script = $this->scriptBuilder->processTrees($test1, $test2);
		//$this->assertEquals($script, $result);
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
	
	static function assertEquals($a, $b)
	{
		if ( is_array($a) && is_array($b) )
		{
			ksort($a);
			ksort($b);
		}
		return parent::assertEquals($a, $b);		
	}
}