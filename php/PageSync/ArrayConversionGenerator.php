<?php

/**
 * This class accepts two arrays, and an equals function, which determines if
 * two items are equivalent, and a convertable function, which determines if
 * one item can be converted to another.
 * 
 * It then returns a sequence of MOVE, INSERT, DELETE, and CONVERT operations
 * corresponding to the shortest sequence of moves needed to convert the first
 * array into the second.
 * 
 * All items in the arrays must be unique by the equalsFunction given,
 * duplicates will cause undetermined behavior.
 * 
 * @author Garfinkel Smith <asdf@fdsa.com>
 */
require_once("PageSync/Exception.php");

class PageSync_ArrayConversionGenerator
{
	const MOVE = 1;
	const DELETE = 2;
	const INSERT = 3;
	const CONVERT = 4;
	
	public static function generateConversion($a, $b, $equalsFunction, $canBeConvertedFunction)
	{
		// Record which items in $a are in $b
		$aValid = self::findIndexIn($a, $b, $equalsFunction);

		// Record which items in $b are in $a
		$bValid = self::findIndexIn($b, $a, $equalsFunction);
		
		$changes = array();
		
		self::generateConversionMoves($a, $aValid, $b, $bValid, $equalsFunction, $changes);

		self::generateConversionInsertionsAndDeletions(
			$a, $aValid, $b, $bValid, $canBeConvertedFunction, $changes);
		
		return $changes;
	}
	
	private static function generateConversions($a, $aStart, $aEnd,
		$b, $bStart, $bEnd, $canBeConvertedFunction, &$changes)
	{
		$aPos = $aStart;
		$bPos = $bStart;
		
		$aMarkPos = $aPos;
		$bMarkPos = $bPos;
		
		$decA = TRUE;
		while ( $aPos > $aEnd && $bPos > $bEnd )
		{
			if (
				$aPos > $aEnd && $bPos > $bEnd &&
				call_user_func($canBeConvertedFunction, $a[$aPos], $b[$bPos]) )
			{
				for($aa=$aMarkPos;$aa>$aPos;$aa--)
				{
					$changes[] = array(self::DELETE, $aa);
				}
				for($bb=$bMarkPos;$bb>$bPos;$bb--)
				{
					$changes[] = array(self::INSERT, $aPos+1, $b[$bb]);
				}

				$changes[] = array(self::CONVERT, $aPos, $b[$bPos]);
				if ( $aPos > $aEnd ) $aPos--;
				if ( $bPos > $bEnd ) $bPos--;
				
				$aMarkPos = $aPos;
				$bMarkPos = $bPos;
			}
			elseif ( $aPos == $aEnd + 1 && $bPos != $bEnd + 1 )
			{
				$bPos--;	
			}
			elseif ( $bPos == $aEnd + 1 )
			{
				$aPos--;
			}
			elseif ( $decA  )
			{
				if ( $aPos > $aEnd ) $aPos--;
				$decA = FALSE;
			}
			else
			{
				if ( $bPos > $bEnd ) $bPos--;
				$decA = TRUE;	
			}
		}
		while( $aMarkPos > $aEnd )
		{
			$changes[] = array(self::DELETE, $aMarkPos);
			$aMarkPos--;
		}
		
		while( $bMarkPos > $bEnd )
		{
			$changes[] = array(self::INSERT, $aMarkPos+1, $b[$bMarkPos]);
			$bMarkPos--;
		}
	}
	
	private static function generateConversionInsertionsAndDeletions(
		$a, $aValid, $b, $bValid, $canBeConvertedFunction, &$changes)
	{
		$aPos = count($a) - 1;
		$aValidPos = count($aValid) - 1;
		$bPos = count($b) - 1;
		$bValidPos = count($bValid) - 1;
		$count = 0;
		while ( $aPos >= 0 || $bPos >= 0 )
		{
			if ( $aPos < -1 ) $aPos = -1;
			if ( $bPos < -1 ) $bPos = -1;
			$aValidCurrent = $aValidPos >=0 ? $aValid[$aValidPos] : -1;
			$bValidCurrent = $aValidPos >=0 ? $bValid[$bValidPos] : -1;
			if ( $aPos > $aValidCurrent && $bPos > $bValidCurrent )
			{
				self::generateConversions(
					$a, $aPos, $aValidCurrent,
					$b, $bPos, $bValidCurrent,
					$canBeConvertedFunction, $changes);
				$aPos = $aValidCurrent;
				$bPos = $bValidCurrent;
			}
			else if ( $aPos == $aValidCurrent && $bPos > $bValidCurrent )
			{
				$changes[] = array(self::INSERT, $aPos+1, $b[$bPos]);
				$bPos--;
			}
			else if ( $aPos > $aValidCurrent && $bPos == $bValidCurrent )
			{
				$changes[] = array(self::DELETE, $aPos);
				$aPos--;
			}
			else
			{
				$aPos--;
				$bPos--;
				$aValidPos--;
				$bValidPos--;
			}
		}
	}
	
	// Finds indexes of $array1 for elements that exist in $array2
	private static function findIndexIn($array1, $array2, $equalsFunction)
	{
		// Record which items in $a are in $b
		$valid = array();
		for($n=0; $n<count($array1); $n++)
		{ 
			$validPos = self::findIn($array1[$n], $array2, 0, count($array2)-1,
				$equalsFunction, TRUE);
			if ( $validPos !== NULL )
			{
				$valid[] = $n;
			}
		}
		return $valid;	
	}
	
	private static function generateConversionMoves(&$a, &$aValid, $b, $bValid,
		$equalsFunction, &$changes)
	{
		while( ! self::compareArrays($a, $aValid, $b, $bValid, $equalsFunction) )
		{	
			$beginning = self::findStart($a, $aValid, $b, $bValid, $equalsFunction);
			
			$last = self::findEnd($a, $aValid, $b, $bValid, $equalsFunction);

			$i = $beginning;
			$n = NULL;
			while( $n === NULL && $i <= $last )
			{
				$neighbors = self::findDesiredNeighbors( $i, $a, $aValid, $b, $bValid, $beginning,
					$last, $equalsFunction, TRUE );				
				if ( $neighbors !== FALSE ) $n = $i;
				$i++;
			}

			$i = $beginning;
			while( $n === NULL && $i <= $last )
			{
				$neighbors = self::findDesiredNeighbors( $i, $a, $aValid, $b, $bValid, $beginning,
					$last, $equalsFunction, FALSE );
				if ( $neighbors !== FALSE ) $n = $i;
				$i++;
			}

			if ( $n === NULL ) PageSync_Exception::throwFatal("Design error");

			list( $previous, $next ) = $neighbors;
			if ( $previous !== NULL )
			{
				$previousPos = self::findIn2($previous, $a, $aValid, $beginning, $last, $equalsFunction);
				if ( $previousPos === FALSE ) PageSync_Exception::throwFatal("Design error");
				if ( $previousPos + 1 == count($aValid) )
				{
					$realDest = count($a);
				}
				else
				{
					$realDest = $aValid[$previousPos+1];
				}
				$changes[] = array(self::MOVE, $aValid[$n], $realDest);
				self::move($a, $aValid, $n, $previousPos+1);
			}
			else
			{
				$nextPos = self::findIn2($next, $a, $aValid, $beginning, $last, $equalsFunction);
				if ( $nextPos === FALSE ) PageSync_Exception::throwFatal("Design error");
				$changes[] = array(self::MOVE, $aValid[$n], $aValid[$nextPos]);
				self::move($a, $aValid, $n, $nextPos);
			}
		}
	}
	
	private static function compareArrays($a, $aValid, $b, $bValid, $equalsFunction)
	{
		if ( count($aValid) != count($bValid) )
			PageSync_Exception::throwFatal(
			"The number of items in the two arrays to be compared must be equal: "
				. count($aValid) . "!=" . count($bValid));
		$count = count($aValid);
		for ( $x=0; $x<$count; $x++ )
		{
			if ( ! call_user_func($equalsFunction, $a[$aValid[$x]], $b[$bValid[$x]], $equalsFunction) ) return FALSE;
		}
		return TRUE;
	}	
	
	private static function findStart($a, $aValid, $b, $bValid, $equalsFunction)
	{
		if ( count($aValid) != count($bValid) ) PageSync_Exception::throwFatal("Design error");
		for ( $x=0; $x<count($aValid); $x++ )
		{
			if ( ! call_user_func($equalsFunction, $a[$aValid[$x]], $b[$bValid[$x]], $equalsFunction) ) return $x;
		}
		PageSync_Exception::throwFatal("Design error");
	}
	
	private static function findEnd($a, $aValid, $b, $bValid, $equalsFunction)
	{
		if ( count($aValid) != count($bValid) ) PageSync_Exception::throwFatal("Design error");
		for ( $x=count($aValid)-1; $x>=0; $x-- )
		{
			if ( ! call_user_func($equalsFunction, $a[$aValid[$x]], $b[$bValid[$x]], $equalsFunction) ) return $x;
		}
		PageSync_Exception::throwFatal("Design error");
	}
	
	private static function findDesiredNeighbors($aPos, $a, $aValid, $b, $bValid, $beginning, $last, $equalsFunction, $noMatch)
	{
		if ( $aPos > count($aValid) - 1 ) PageSync_Exception::throwFatal("aPos greater than length of aValid");
		$aPrevious = ( $aPos == $beginning ? NULL : $a[$aValid[$aPos-1]] );
		$aNext = ( $aPos == $last ? NULL : $a[$aValid[$aPos+1]] );
		$bPos = self::findIn2($a[$aValid[$aPos]], $b, $bValid, $beginning, $last, $equalsFunction);
		if ( $bPos === FALSE || $bPos === NULL ) PageSync_Exception::throwFatal("Design error");
		$bPrevious = ( $bPos == $beginning ? NULL : $b[$bValid[$bPos-1]] );
		$bNext = ( $bPos == $last ? NULL : $b[$bValid[$bPos+1]] );
		if ( $noMatch )
		{
			if ( $aPrevious !== NULL && $bPrevious !== NULL &&
				call_user_func($equalsFunction, $aPrevious, $bPrevious) ) return FALSE;
			if ( $aNext !== NULL && $bNext !== NULL &&
				call_user_func($equalsFunction, $aNext, $bNext) ) return FALSE;
		}
		else
		{
			if ( ( $aPrevious !== NULL && $bPrevious !== NULL &&
				call_user_func($equalsFunction, $aPrevious, $bPrevious) ) &&
				( $aNext !== NULL && $bNext !== NULL &&
				call_user_func($equalsFunction, $aNext, $bNext) ) ) return FALSE;
		} 
		return array($bPrevious, $bNext);
	}
	
	private static function findIn($needle, $haystack, $beginning, $last, $equalsFunction, $allowNull = FALSE)
	{
		for ( $n = $beginning; $n <= $last; $n++ )
		{
			if ( call_user_func($equalsFunction, $haystack[$n], $needle) ) return $n;	
		}
		if ( $allowNull) return NULL;
		PageSync_Exception::throwFatal("Design error");
	}
	
	private static function findIn2($needle, $haystack, $haystackValid, $beginning, $last, $equalsFunction, $allowNull = FALSE)
	{
		for ( $n = $beginning; $n <= $last; $n++ )
		{
			if ( call_user_func($equalsFunction, $haystack[$haystackValid[$n]], $needle) ) return $n;	
		}
		if ( $allowNull) return NULL;
		PageSync_Exception::throwFatal("Design error");
	}
	
	private static function move(&$a, &$aValid, $itemPos, $dest)
	{
		if ( $dest == count($aValid) )
		{
			$realDest = count($a);
		}
		else
		{
			$realDest = $aValid[$dest];	
		}
		if ( $aValid[$itemPos] == $realDest ) PageSync_Exception::throwFatal("Design error");
		if ( $realDest < $aValid[$itemPos] )
		{
			$saved = $a[$aValid[$itemPos]];
			array_splice($a, $aValid[$itemPos], 1);
			array_splice($a, $realDest, 0, array($saved));
			$saved = $aValid[$itemPos];
			if ( $itemPos - $dest != $aValid[$itemPos] - $realDest )
			{
				for($n=$itemPos;$n>$dest;$n--)
				{
					$aValid[$n] = $aValid[$n-1]+1;
				}
			}
		}
		else
		{
			array_splice($a, $realDest, 0, array($a[$aValid[$itemPos]]));
			array_splice($a, $aValid[$itemPos], 1);
			$placesMoved = ( $realDest - $aValid[$itemPos] ) -1 ;
			
			array_splice($aValid, $dest, 0, array($aValid[$itemPos]));
			array_splice($aValid, $itemPos, 1);
			for($n=$itemPos;$n<$dest-1;$n++)
			{
				$aValid[$n] = $aValid[$n]-1;
			}
			$aValid[$dest-1] += $placesMoved;
		}
	}
}