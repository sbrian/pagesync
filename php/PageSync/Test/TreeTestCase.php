<?php

require_once("PHPUnit/Framework/TestCase.php");
require_once("PageSync/Tree.php");

class PageSync_Test_TreeTestCase extends PHPUnit_Framework_TestCase
{
	
	public function test0()
	{
		$xml = "<a><b><c/></b></a>";
		$tree =& PageSync_Tree::parse($xml);
		$tree[PageSync_Tree::INFO_CHILDREN][0]
			[PageSync_Tree::INFO_CHILDREN][] = "d";
		$a = count($tree[PageSync_Tree::INFO_CHILDREN][0]
			[PageSync_Tree::INFO_CHILDREN]) . "\n";
		$b = count($tree[PageSync_Tree::INFO_CHILDREN][0]
			[PageSync_Tree::INFO_PARENT]
			[PageSync_Tree::INFO_CHILDREN][0]
			[PageSync_Tree::INFO_CHILDREN]) . "\n";
		$this->assertEquals($a, $b);
	}
	
	public function test00()
	{
		$xml = "<a><b><c/></b></a>";
		$tree =& PageSync_Tree::parse($xml);
		$n0 =& $tree[PageSync_Tree::INFO_CHILDREN][0]
			[PageSync_Tree::INFO_CHILDREN];
		$n1 =& $tree[PageSync_Tree::INFO_CHILDREN][0]
			[PageSync_Tree::INFO_PARENT]
			[PageSync_Tree::INFO_CHILDREN][0]
			[PageSync_Tree::INFO_CHILDREN];
		$tree[PageSync_Tree::INFO_CHILDREN][0]
			[PageSync_Tree::INFO_CHILDREN][] = "d";
		$this->assertEquals(count($n0), 2);
		$this->assertEquals(count($n1), 2);
	}
	
	public function test1()
	{
		$xml = "<a><b><c/></b></a>";
		$tree =& PageSync_Tree::parse($xml);
		$tree[PageSync_Tree::INFO_CHILDREN][] = "d";
		$a = count($tree[PageSync_Tree::INFO_CHILDREN]) . "\n";
		$b = count($tree[PageSync_Tree::INFO_CHILDREN][0]
			[PageSync_Tree::INFO_PARENT]
			[PageSync_Tree::INFO_CHILDREN]) . "\n";
		$this->assertEquals($a, $b);
	}
	
	public function test01()
	{
		$xml = "<a><b><c/></b></a>";
		$tree =& PageSync_Tree::parse($xml);
		$n0 =& $tree[PageSync_Tree::INFO_CHILDREN];
		$n1 =& $tree[PageSync_Tree::INFO_CHILDREN][0]
			[PageSync_Tree::INFO_PARENT]
			[PageSync_Tree::INFO_CHILDREN];
		$n0[] = "d";
		$this->assertEquals(count($n0), 2);
		$this->assertEquals(count($n1), 2);
	}
}