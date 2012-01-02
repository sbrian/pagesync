<?php

require_once("PageSync/Tree.php");
require_once("PageSync/Exception.php");

/**
 *  @author Garfinkel Smith <asdf@fdsa.com>
 */
class PageSync_Map
{
	private $map;
	
	public function PageSync_Map($map)
	{
		$this->map = $map;
	}
	
	public function equalsInMap($n0, $n1)
	{
		$res = $n0[PageSync_Tree::INFO_ID]
			=== $n1[PageSync_Tree::INFO_ID];
		if ( $res )
		{
			return TRUE;
		}
		$res = $this->map[$n0[PageSync_Tree::INFO_ID]] ===
			$n1[PageSync_Tree::INFO_ID];
		if ( $res )
		{
			return TRUE;
		}
		$res = $this->map[$n1[PageSync_Tree::INFO_ID]] ===
			$n0[PageSync_Tree::INFO_ID];
		return $res;
	}
	
	public function nodeAlmostEquals($n0, $n1)
	{
		if ( $n0[PageSync_Tree::INFO_TYPE] != $n1[PageSync_Tree::INFO_TYPE] )
		{
			return FALSE;
		}
		if ( $n0[PageSync_Tree::INFO_TYPE] == PageSync_Tree::TYPE_TEXT )
		{
			return TRUE;
		}
		if ( $n0[PageSync_Tree::INFO_TYPE] == PageSync_Tree::TYPE_ATTRIBUTE )
		{
			return $n0[PageSync_Tree::INFO_NAME] ==
				$n1[PageSync_Tree::INFO_NAME];
		}
		if ( $n0[PageSync_Tree::INFO_NAME] != $n1[PageSync_Tree::INFO_NAME] )
		{
			return FALSE;
		}
		$n0Children = $n0[PageSync_Tree::INFO_CHILDREN];
		$n1Children = $n1[PageSync_Tree::INFO_CHILDREN];
		if ( count($n0Children) != count($n1Children) )
		{
			return FALSE;
		}
		for( $i=0;$i<count($n0Children);$i++ )
		{
			if ( ! self::nodeAlmostEquals($n0Children[$i], $n1Children[$i]) )
				return FALSE;
		}
		return TRUE;
	}
	
	public function get($id)
	{
		return $this->map[$id];
	}
}