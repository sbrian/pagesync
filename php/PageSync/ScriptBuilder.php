<?php

require_once("PageSync/Exception.php");
require_once("PageSync/Tree.php");
require_once("PageSync/Map.php");
require_once("PageSync/ArrayConversionGenerator.php");

/**
 *  @author Garfinkel Smith <asdf@fdsa.com>
 */
class PageSync_ScriptBuilder
{	
	// Operation: [DELETE, $xpath]
	const DELETE = 1;
	
	// Operation: [APPEND_COPY_AS_CHILD, $sourceXpath, $parentXpath]
	const APPEND_COPY_AS_CHILD = 2;
	
	// Operation: [INSERT_COPY_BEFORE, $sourceXpath, $siblingXpath]
	const INSERT_COPY_BEFORE = 3;
	
	// Operation: [APPEND_CHILD_NODE, $newNode (PageSync_Tree), $parentXpath]
	const APPEND_CHILD_NODE = 4;
	
	// Operation: [INSERT_NODE_BEFORE, $newNode, (PageSync_Tree), $siblingXpath]
	const INSERT_NODE_BEFORE = 5;
	
	// Operation: [SET_TEXT_VALUE, $xpath, $newValue]
	const SET_TEXT_VALUE = 6;
	
	// Operation: [SET_ATTRIBUTE, $parentXpath, $name, $newValue]
	const SET_ATTRIBUTE = 7;
	
	const FAST = 1;
	const ACCURATE = 2;
	
	// If a node has no children, but is otherwise identical to another node with
	// children, the maximum number of children the second node can have to be
	// considered a match.
	private $ZERO_LIMIT = 2;
	
	// The percentage of the number of children of the node with the most children
	// that must match a node in the other node for two nodes to be considered to match.
	//
	// Value of 1 requires perfect matches.
	private $RATIO_LIMIT = 0.3;

	private $mode = self::FAST;
	
	public function PageSync_ScriptBuilder($mode = self::FAST, $ratioLimit = 0.3, $zeroLimit = 2)
	{
		$this->RATIO_LIMIT = $ratioLimit;
		$this->ZERO_LIMIT = $zeroLimit;
		$this->mode = $mode;
	}

	public function processTrees($str0, $str1)
	{
		$tree0 =& PageSync_Tree::parse($str0);
		$tree1 =& PageSync_Tree::parse($str1);
		
		$map = array();
		$reverseMap = array();
		$this->compareChildLists($tree0, $tree1, $map, $reverseMap);

		$mapObject = new PageSync_Map($map);
		
		$scripts = $this->depthFirstScriptGeneration($tree0, $tree1, $mapObject);
		
		return $scripts;
	}

	private static function depthFirstScriptGeneration(&$node0, $node1, $mapObject)
	{
		if ( $node0[PageSync_Tree::INFO_TYPE] !== PageSync_Tree::TYPE_ELEMENT )
			return array();
		if ( ! is_array($node0[PageSync_Tree::INFO_CHILDREN]) )
			PageSync_Exception::throwFatal("Element's children are not an array");
		$mappedNodeID = $mapObject->get($node0[PageSync_Tree::INFO_ID]);
		if ( ! $mappedNodeID ) return array();
		$mappedNode =& PageSync_Tree::findByID($node1, $mappedNodeID);
		if ( ! $mappedNode )
			PageSync_Exception::throwFatal("Unable to find mapped node");
		$scripts = array();
		foreach($node0[PageSync_Tree::INFO_CHILDREN] as &$child)
		{
			$scripts = array_merge($scripts,
			self::depthFirstScriptGeneration($child, $node1, $mapObject));
		}
		$thisScript = self::generateScript($node0, $mappedNode, $mapObject);
		
	 	$scripts = array_merge($scripts, $thisScript);
	 	return $scripts;
	}

	private static function generateScript(&$node0, $node1, $mapObject)
	{	
		$treeArray0 =& $node0[PageSync_Tree::INFO_CHILDREN];
		$treeArray1 =& $node1[PageSync_Tree::INFO_CHILDREN];
		
		$conversionScript = PageSync_ArrayConversionGenerator::generateConversion(
			$treeArray0, $treeArray1, array($mapObject, "equalsInMap"),
				array($mapObject, "nodeAlmostEquals"));

		$steps = array();

		foreach($conversionScript as $entry)
		{
			switch($entry[0])
			{
				case PageSync_ArrayConversionGenerator::INSERT:
					self::processInsertion($node0,
						$entry[1],
						$entry[2],
						$steps);
					break;
				case PageSync_ArrayConversionGenerator::DELETE:
					self::processDeletion($node0,
						$entry[1],
						$steps);
					break;
				case PageSync_ArrayConversionGenerator::MOVE:
					self::processMove($node0,
						$entry[1],
						$entry[2],
						$steps);
					break;
				case PageSync_ArrayConversionGenerator::CONVERT:
					self::processConvert($node0,
						$entry[1],
						$entry[2],
						$steps);
					break;
			}
		}
		return $steps;
	}
	
	private static function processInsertion(&$node0, $targetPos, $newNode, &$steps)
	{
		if ( $newNode[PageSync_Tree::INFO_TYPE] ==
			PageSync_Tree::TYPE_ATTRIBUTE )
		{
			$steps[] = array(
				self::SET_ATTRIBUTE,
				PageSync_Tree::getXpath($node0),
				$newNode[PageSync_Tree::INFO_NAME],
				$newNode[PageSync_Tree::INFO_VALUE]);
			return;
		}
		if ( $targetPos == count($node0[PageSync_Tree::INFO_CHILDREN]) )
		{
			if ( ! $node0 )
			{
				PageSync_Exception::throwFatal("Parent node does not exist");
			}
			$steps[] = array(self::APPEND_CHILD_NODE,
				PageSync_Tree::getXpath($node0),
				PageSync_Tree::detachNode($newNode)
				);
			$newNodeArray = array();
			$newNode0 =& PageSync_Tree::appendChild($node0, $newNode);
			$treeArray[] =& $newNode0;
		}
		else
		{
			$targetNode =& $node0[PageSync_Tree::INFO_CHILDREN][$targetPos];
			$steps[] = array(self::INSERT_NODE_BEFORE,
				PageSync_Tree::getXpath($targetNode),
				PageSync_Tree::detachNode($newNode)
				);
			$newSibling =& PageSync_Tree::prependSibling($targetNode, $newNode);
			$newSiblingArray = array();
			$newSiblingArray[] =& $newSibling;
		}
	}
	
	private static function processDeletion(&$node0, $targetPos, &$steps)
	{
		$steps[] = array(self::DELETE,
			PageSync_Tree::getXpath(
			$node0[PageSync_Tree::INFO_CHILDREN][$targetPos]));
		PageSync_Tree::deleteNode(
			$node0[PageSync_Tree::INFO_CHILDREN][$targetPos]);
	}
	
	private static function processMove(&$node0, $sourcePos, $destPos, &$steps)
	{
		$nodeToMove =& $node0[PageSync_Tree::INFO_CHILDREN][$sourcePos];
		if ( $destPos == count($node0[PageSync_Tree::INFO_CHILDREN]) )
		{
			$parentNode0 =& $nodeToMove[PageSync_Tree::INFO_PARENT];
			if ( ! $parentNode0 )
			{
				Hush_Error::throwFatal("Parent node does not exist");
			}
			$steps[] = array(self::APPEND_COPY_AS_CHILD,
				PageSync_Tree::getXpath($nodeToMove),
				PageSync_Tree::getXpath($parentNode0));
			$newNodeArray = array();
			$newNode0 =& PageSync_Tree::appendChild($parentNode0, $nodeToMove);
			$newNodeArray[] =& $newNode0;
		}
		else
		{
			$targetNode =& $node0[PageSync_Tree::INFO_CHILDREN][$destPos];
			$steps[] = array(self::INSERT_COPY_BEFORE,
				PageSync_Tree::getXpath($nodeToMove),
				PageSync_Tree::getXpath($targetNode)
				);
			PageSync_Tree::prependSibling($targetNode, $nodeToMove);
		}
		
		PageSync_Tree::deleteNode($nodeToMove);
		
	}
	
	private static function processConvert(&$node0, $targetPos, $newNode, &$steps)
	{
		if ( $newNode[PageSync_Tree::INFO_TYPE] ==
			PageSync_Tree::TYPE_ATTRIBUTE )
		{
			$steps[] = array(
				self::SET_ATTRIBUTE,
				PageSync_Tree::getXpath($node0),
				$newNode[PageSync_Tree::INFO_NAME],
				$newNode[PageSync_Tree::INFO_VALUE]);
			return;
		}
		else if ( $newNode[PageSync_Tree::INFO_TYPE] ==
			PageSync_Tree::TYPE_TEXT )
		{
			$steps[] = array(
				self::SET_TEXT_VALUE,
				PageSync_Tree::getXpath($node0[PageSync_Tree::INFO_CHILDREN][$targetPos]),
					$newNode[PageSync_Tree::INFO_VALUE]);
			return;
		}
		$n0Children = $node0[PageSync_Tree::INFO_CHILDREN][$targetPos]
			[PageSync_Tree::INFO_CHILDREN];
		$n1Children = $newNode[PageSync_Tree::INFO_CHILDREN];
		if ( count($n0Children) != count($n1Children) )
		{
			PageSync_Exception::throwFatal("Child count should never be different");
		}
		for( $i=0;$i<count($n0Children);$i++ )
		{
			self::processConvert($node0[PageSync_Tree::INFO_CHILDREN][$targetPos], $i,
				$n1Children[$i], $steps);
		}
		return TRUE;
	}
	
	public function getIdForMap($n)
	{
		return $n[PageSync_Tree::INFO_ID];	
	}
	
	public function nodeEquals($n0, $n1, &$map, &$reverseMap)
	{
		$marker = $n0[PageSync_Tree::INFO_ID].":".$n1[PageSync_Tree::INFO_ID];
		if ( isset($this->equalsCache[$marker]) )
		{
			if ( $this->equalsCache[$marker] === TRUE ) return TRUE;
			if ( $this->equalsCache[$marker] === FALSE ) return FALSE;
			foreach($this->equalsCache[$marker] as $k=>$v)
			{
				$map[$k] = $v;
				$reverseMap[$v] = $k;	
			}
			return TRUE;
		}
		$returnValue = $this->_nodeEquals($n0,$n1, $map, $reverseMap);
		$this->equalsCache[$marker] = $returnValue;
		return $returnValue;
	}
	
	/**
	 * If successful, returns TRUE, or a map of children that matched if there were children.
	 * If fails, returns FALSE.
	 */
	public function _nodeEquals($n0, $n1, &$map, &$reverseMap)
	{
		if ( $n0[PageSync_Tree::INFO_TYPE] != $n1[PageSync_Tree::INFO_TYPE] )
		{
			return FALSE;
		}
		if ( $n0[PageSync_Tree::INFO_TYPE] == PageSync_Tree::TYPE_TEXT )
		{
			return 	$n0[PageSync_Tree::INFO_VALUE] == $n1[PageSync_Tree::INFO_VALUE];
		}
		if ( $n0[PageSync_Tree::INFO_TYPE] == PageSync_Tree::TYPE_ATTRIBUTE )
		{
			return 	$n0[PageSync_Tree::INFO_VALUE] == $n1[PageSync_Tree::INFO_VALUE]
				&& $n0[PageSync_Tree::INFO_NAME] == $n1[PageSync_Tree::INFO_NAME];
		}
		if ( $n0[PageSync_Tree::INFO_NAME] != $n1[PageSync_Tree::INFO_NAME] )
		{
			return FALSE;
		}
		
		$key = $n0[PageSync_Tree::INFO_ID] . ":" . $n1[PageSync_Tree::INFO_ID];
		
		$children0 = $n0[PageSync_Tree::INFO_CHILDREN];
		$children1 = $n1[PageSync_Tree::INFO_CHILDREN];
		
		$childrenCount0 = count($children0);
		$childrenCount1 = count($children1);
		
		if ( $childrenCount0 == 0 || $childrenCount1 == 0 )
		{
			if ( $childrenCount0 == 0 && $childrenCount1 == 0 ) return TRUE;
			return max($childrenCount0, $childrenCount1) < $this->ZERO_LIMIT;
		}
		
		if ( min($childrenCount0, $childrenCount1)  / max($childrenCount0, $childrenCount1)  < $this->RATIO_LIMIT )
		{
			return FALSE;
		}
		
		$newMap = array();
		$newReverseMap = array();
		$matchedChildrenOneLevel = $this->compareChildLists($n0, $n1, $newMap, $newReverseMap);
		if (  $matchedChildrenOneLevel / max($childrenCount0, $childrenCount1) 
			< $this->RATIO_LIMIT )
		{
			return FALSE;
		}

		$map = $map + $newMap;
		$reverseMap = $reverseMap + $newReverseMap;
		return $newMap;
	}

	private function compareChildLists($n0, $n1, &$map, &$reverseMap)
	{	
		$map[$n0[PageSync_Tree::INFO_ID]] = $n1[PageSync_Tree::INFO_ID];
		
		$reverseMap = self::flipArray($map);
		
		$children0 = $n0[PageSync_Tree::INFO_CHILDREN];
		$children1 = $n1[PageSync_Tree::INFO_CHILDREN];
		
		$childrenCount0 = count($children0);
		$childrenCount1 = count($children1);
		
		if ( $this->mode == self::ACCURATE )
		{
			$mappedCount = $this->lcs($children0, $children1, $map, $reverseMap);
		}
		else if ( $this->mode == self::FAST )
		{
			$mappedCount = $this->mapOrderedNodes($children0, $children1, $map, $reverseMap);
		}
		else
		{
			PageSync_Exception::throwFatal("Invalid mode: " . $this->mode);
		}
			
		if ( $mappedCount == $childrenCount0 ||
			$mappedCount == $childrenCount1 )
		{
			return $mappedCount;
		}
		
		$mappedCount3 = $this->mapOrderedNodes($children0, $children1, $map, $reverseMap);
		
		$mappedCount2 = $this->mapByLinearSearch($children0, $children1, $map, $reverseMap);
		
		return $mappedCount3 + $mappedCount2 + $mappedCount;
	}
	
	private function lcs($X, $Y, &$map, &$reverseMap)
	{
		$N = count($X);
		$M = count($Y);
		if ( $N == 0  || $M  == 0 ) return 0;
		$max = $N + $M;
		$v = array_fill(0, 2*$max+1, 0);
		$common = array_fill(0, 2*$max+1, array());
		for ( $D = 0 ; $D < $max + 1; $D++ )
		{
			for ( $k = -$D; $k < $D+1; $k += 2 )
			{
				if ( $k == -$D || $k != $D && $v[$k-1] < $v[$k+1] )
				{
					$x = $v[$k+1];
					$common[$k] = $common[$k+1];
				}
				else
				{
					$x = $v[$k-1] + 1;
					$common[$k] = $common[$k-1];
				}
                
				$y = $x - $k;

				while ( $x < $N && $y < $M )
				{
					$tmpMap = array();
					$tmpReverseMap = array();
					if ( ! $this->nodeEquals($X[$x], $Y[$y], $tmpMap, $tmpReverseMap) )
					{
						break;
					}
					$kArray = array();
					$kArray[] = $x;
					$kArray[] = $y;
					$kArray[] = $tmpMap;
					$kArray[] = $tmpReverseMap;
					$common[$k][] = $kArray;
					unset($kArray);
					$x += 1;
					$y += 1;
				}

				$v[$k] = $x;
				if ( $x >= $N && $y >= $M )
				{
					$res = 0;
					foreach ( $common[$k] as $kEntry )
					{
						foreach( $kEntry[2] as $kk=>$vv)
						{
							$map[$kk] = $vv;	
						}
						foreach( $kEntry[3] as $kk=>$vv)
						{
							$reverseMap[$kk] = $vv;	
						}
						$map[$X[$kEntry[0]][PageSync_Tree::INFO_ID]]
							= $Y[$kEntry[1]][PageSync_Tree::INFO_ID];
						$reverseMap[$Y[$kEntry[1]][PageSync_Tree::INFO_ID]]
							= $X[$kEntry[0]][PageSync_Tree::INFO_ID];
						$res++;
					}
					return $res;
				}
			}
		}
	}
	
	
	private function mapOrderedNodes($children0, $children1, &$map, &$reverseMap)
	{
		// TODO: this could probably be made more efficient
		// by alternating incrementing the counters.
		$pos0 = 0;
		$pos1 = 0;
		$mappedCount = 0;
		while( $pos0 < count($children0) && $pos1 < count($children1) )
		{
			if ( isset($map[$children0[$pos0][PageSync_Tree::INFO_ID]]) )
			{
				$pos0++;
				continue;	
			}
			$pos1a = $pos1;
			while( $pos1a < count($children1) )
			{
				if ( isset($reverseMap[$children1[$pos1a][PageSync_Tree::INFO_ID]]) )
				{
					$pos1a++;
					continue;	
				}
				if ( $this->nodeEquals($children0[$pos0], $children1[$pos1a], $map, $reverseMap) )
				{
					$map[$children0[$pos0][PageSync_Tree::INFO_ID]]
						= $children1[$pos1a][PageSync_Tree::INFO_ID];
					$reverseMap[$children1[$pos1a][PageSync_Tree::INFO_ID]]
						= $children0[$pos0][PageSync_Tree::INFO_ID];
					$pos1 = ++$pos1a;
					$mappedCount++;
					break;
				}
				$pos1a++;
			}
			$pos0++;
		}
		return $mappedCount;
	}
	
	/**
	 * After an LCS, check all remaining values for out-of-order matches.
	 */
	private function mapByLinearSearch($children0, $children1, &$map, &$reverseMap)
	{
		$mappedCount = 0;
		
		//$reverseMap = self::flipArray($map);
		
		// Fill in out of order matches by linear search
		foreach( $children0 as &$c0 )
		{
			if ( ! isset($map[$c0[PageSync_Tree::INFO_ID]]) )
			foreach( $children1 as &$c1 )
			{
				if ( ! isset($reverseMap[$c1[PageSync_Tree::INFO_ID]])
					&& $this->nodeEquals($c0, $c1, $map, $reverseMap) )
				{
					$map[$c0[PageSync_Tree::INFO_ID]] = $c1[PageSync_Tree::INFO_ID];
					$reverseMap[$c1[PageSync_Tree::INFO_ID]] = $c0[PageSync_Tree::INFO_ID];
					$mappedCount++;
					break;
				}
			}
		}
		
		return $mappedCount;
	}
	
	private static function flipArray($array)
	{
		$flipped = array();
		foreach($array as $k=>$v)
		{
			$flipped[$v] = $k;
		}
		return $flipped;
	}
	
	private static function removeChildrenOfUnmappedParents(&$arr, $map)
	{
		for($i=count($arr)-1; $i>=0; $i--)
		{
			if ( $arr[$i][PageSync_Tree::INFO_PARENT] &&
				$arr[$i][PageSync_Tree::INFO_PARENT][PageSync_Tree::INFO_PARENT] &&
				! isset($map[$arr[$i][PageSync_Tree::INFO_PARENT][PageSync_Tree::INFO_ID]]) )
			{
				array_splice($arr, $i, 1);
			}
		}
	}
	
	public static function dumpScript($script)
	{
		$result = "";
		foreach($script as $line)
		{
			$result .= self::dumpScriptLine($line) . "\n";
		}
		return $result;
	}
	
	public static function dumpScriptLine($line)
	{
		$action = array_shift($line);
		$actionString = self::actionToString($action);
		foreach($line as $param)
		{
			if ( strpos("\n", $param) !== FALSE )
			{
				$param = substr($param, 0, 8) . "...";	
			}
			$actionString .= "," . $param;
		}
		return $actionString;
	}
	
	public static function actionToString($action)
	{
		switch($action)
		{
			case self::DELETE:
				return "DELETE";
			case self::APPEND_COPY_AS_CHILD:
				return "APPEND_COPY_AS_CHILD";
			case self::INSERT_COPY_BEFORE:
				return "INSERT_COPY_BEFORE";
			case self::APPEND_CHILD_NODE:
				return "APPEND_CHILD_NODE";
			case self::INSERT_NODE_BEFORE:
				return "INSERT_NODE_BEFORE";
			case self::SET_TEXT_VALUE:
				return "SET_TEXT_VALUE";
			case self::SET_ATTRIBUTE:
				return "SET_ATTRIBUTE";
		}
		PageSync_Exception::throwFatal("Unknown script action: $action");
	}
}
