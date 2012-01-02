<?php

require_once("PHPUnit/Framework/TestCase.php");
require_once("PageSync/ArrayConversionGenerator.php");


/* 
 * phpunit PageSync_Test_ArrayConversionGeneratorTestCase PageSync/ArrayConversionGeneratorTestCase.php
 * 
 * 
 */
class PageSync_Test_ArrayConversionGeneratorTestCase extends PHPUnit_Framework_TestCase
{
	static $equalsFunction = array("PageSync_Test_ArrayConversionGeneratorTestCase", "equals");
	static $canBeConvertedFunction = array("PageSync_Test_ArrayConversionGeneratorTestCase", "canBeConverted");
	
	public function equals($a, $b)
	{
		//echo strtoupper($a) . " and " . strtoupper($b) . "\n";
		//echo ( $a === $b ? "true\n" : "false\n" );
		return $a === $b;
	}
	
	public function canBeConverted($a, $b)
	{
		return strpos($a, $b) !== FALSE || strpos($b, $a) !== FALSE;
	}
	
	public function testArray1()
	{
		$a = array(
		'b',
		'c',
		'd',
		'e',
		'f',
		'a',
		);

		$b = array(
		'a',
		'b',
		'c',
		'd',
		'e',
		'f',
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::MOVE, 5, 0)
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);
		
		$this->assertEquals($result, $desired);
	}
	
	public function testArray2()
	{
		$a = array(
		'b',
		'c',
		'e',
		'd',
		'f',
		'a',
		);

		$b = array(
		'a',
		'b',
		'c',
		'd',
		'e',
		'f',
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::MOVE, 2, 4),
			array(PageSync_ArrayConversionGenerator::MOVE, 5, 0)
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);
		
		$this->assertEquals($result, $desired);
	}
	
	public function testArray3()
	{
		$a = array(
		'g',
		'b',
		'c',
		'e',
		'd',
		'f',
		'a',
		);

		$b = array(
		'a',
		'b',
		'c',
		'd',
		'e',
		'f',
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::MOVE, 3, 5),
			array(PageSync_ArrayConversionGenerator::MOVE, 6, 1),
			array(PageSync_ArrayConversionGenerator::DELETE, 0),
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);
		
		$this->assertEquals($result, $desired);
	}
	
	public function testArray4()
	{
		$a = array(
		'g',
		'b',
		'c',
		'e',
		'd',
		'f',
		'a',
		);

		$b = array(
		'a',
		'b',
		'c',
		'h',
		'd',
		'e',
		'f',
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::MOVE, 3, 5),
			array(PageSync_ArrayConversionGenerator::MOVE, 6, 1),
			array(PageSync_ArrayConversionGenerator::INSERT, 4, 'h'),
			array(PageSync_ArrayConversionGenerator::DELETE, 0),
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);
		
		$this->assertEquals($result, $desired);
	}

	public function testArray5()
	{
		$a = array(
		'b',
		);

		$b = array(
		'g',
		'b',
		'a',
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::INSERT, 1, 'a'),
			array(PageSync_ArrayConversionGenerator::INSERT, 0, 'g'),
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);
		
		$this->assertEquals($result, $desired);
	}
	
	public function testArray6()
	{
		$a = array(
		'c',
		'a',
		'b',
		'x',
		'e',
		'f',
		'd'
		);

		$b = array(
		'a',
		'b',
		'c',
		'd',
		'e',
		'f',
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::MOVE, 0, 4),
			array(PageSync_ArrayConversionGenerator::MOVE, 6, 4),
			array(PageSync_ArrayConversionGenerator::DELETE, 2),
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);
		
		$this->assertEquals($result, $desired);
	}
	
	public function testArray7()
	{
		$a = array(
		'x',
		'w',
		'y',
		'b',
		);

		$b = array(
		'w',
		'x',
		'y',
		'b',
		'z',
		'a',
		'bb'
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::MOVE, 0, 2),
			array(PageSync_ArrayConversionGenerator::INSERT, 4, 'bb'),
			array(PageSync_ArrayConversionGenerator::INSERT, 4, 'a'),
			array(PageSync_ArrayConversionGenerator::INSERT, 4, 'z')
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);
		
		$this->assertEquals($result, $desired);
	}
	
	public function testArray8()
	{
		$a = array(
		'x',
		'w',
		'y',
		'b',
		);

		$b = array(
		'yyy',
		'w',
		'x',
		'y',
		'b',
		'z',
		'a',
		'bb'
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::MOVE, 0, 2),
			array(PageSync_ArrayConversionGenerator::INSERT, 4, 'bb'),
			array(PageSync_ArrayConversionGenerator::INSERT, 4, 'a'),
			array(PageSync_ArrayConversionGenerator::INSERT, 4, 'z'),
			array(PageSync_ArrayConversionGenerator::INSERT, 0, 'yyy')
			
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);

		$this->assertEquals($result, $desired);
	}
	
	public function testArray9()
	{
		$a = array(
		'b',
		);

		$b = array(
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::DELETE, 0)
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);

		$this->assertEquals($result, $desired);
	}
	
	public function testArray10()
	{
		$a = array(
			'b', 'c', 'd', 'a'
		);

		$b = array(
			'a', 'b', 'c', 'd'
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::MOVE, 3, 0)
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);

		$this->assertEquals($result, $desired);
	}
	
	public function testArray11()
	{
		$a = array(
			'a', 'b', 'c', 'd'
		);
		
		$b = array(
			'b', 'c', 'd', 'a'
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::MOVE, 0, 4)
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);

		$this->assertEquals($result, $desired);
	}
	
	public function testArray12()
	{
		$a = array(
			'b', 'c', 'a',
		);
		
		$b = array(
			'a', 'b', 'c',
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::MOVE, 2, 0)
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);

		$this->assertEquals($result, $desired);
	}
	
	public function testArray13()
	{
		$a = array(
			'a', 'b', 'e', 'c'
			//'a', 'd', 'b', 'e', 'c'
		);
		
		$b = array(
			'c', 'e', 'b', 'a'
			//'c', 'd', 'e', 'b', 'a'
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::MOVE, 0, 2),
			array(PageSync_ArrayConversionGenerator::MOVE, 2, 4),
			array(PageSync_ArrayConversionGenerator::MOVE, 0, 4),
			array(PageSync_ArrayConversionGenerator::MOVE, 0, 4)
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);

		$this->assertEquals($result, $desired);
	}
	
	public function testArray14()
	{
		$a = array(
			'a', 'b', 'c',
		);
		
		$b = array(
			'b', 'c', 'g', 'a'
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::MOVE, 0, 3),
			array(PageSync_ArrayConversionGenerator::INSERT, 2, 'g'),
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);

		$this->assertEquals($result, $desired);
	}
	
	public function testArray15()
	{
		$a = array(
			'b', 'c', 'x', 'a',
		);
		
		$b = array(
			'a', 'b', 'c',
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::MOVE, 3, 0),
			array(PageSync_ArrayConversionGenerator::DELETE, 3)
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);

		$this->assertEquals($result, $desired);
	}
	
	public function testArray16()
	{
		$a = array(
			'x', 'c', 'xx', 'b', 'xxx', 'd', 'xxxx', 'a', 'xxxxx'
		);
		
		$b = array(
			'y', 'a', 'yy', 'b', 'yyy', 'c', 'yyyy', 'd', 'yyyyy'
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::MOVE, 1, 5),
			array(PageSync_ArrayConversionGenerator::MOVE, 7, 2),
			array(PageSync_ArrayConversionGenerator::DELETE, 8),
			array(PageSync_ArrayConversionGenerator::DELETE, 7),
			array(PageSync_ArrayConversionGenerator::INSERT, 7, 'yyyyy'),
			array(PageSync_ArrayConversionGenerator::INSERT, 6, 'yyyy'),
			array(PageSync_ArrayConversionGenerator::DELETE, 4),
			array(PageSync_ArrayConversionGenerator::INSERT, 4, 'yyy'),
			array(PageSync_ArrayConversionGenerator::INSERT, 3, 'yy'),
			array(PageSync_ArrayConversionGenerator::DELETE, 1),
			array(PageSync_ArrayConversionGenerator::DELETE, 0),
			array(PageSync_ArrayConversionGenerator::INSERT, 0, 'y')
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);
		$this->assertEquals($result, $desired);
	}
	
	public function testArray17()
	{
		$a = array(
			'e'
		);
		
		$b = array(
			'f'
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::DELETE, 0),
			array(PageSync_ArrayConversionGenerator::INSERT, 0, 'f'),
			
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);
		$this->assertEquals($result, $desired);
	}
	
	public function testArray18()
	{
		$a = array(
			'a', 'b'
		);
		
		$b = array(
			'c', 'd'
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::DELETE, 1),
			array(PageSync_ArrayConversionGenerator::DELETE, 0),
			array(PageSync_ArrayConversionGenerator::INSERT, 0, 'd'),
			array(PageSync_ArrayConversionGenerator::INSERT, 0, 'c'),
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);
		$this->assertEquals($result, $desired);
	}
	
	public function testArray19()
	{
		$a = array(
			'a'
		);
		
		$b = array(
			'aa'
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::CONVERT, 0, 'aa'),
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);
		$this->assertEquals($result, $desired);
	}
	
	public function testArray20()
	{
		$a = array(
			'a', 'b', 'c'
		);
		
		$b = array(
			'aa', 'x', 'bb', 'd'
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::DELETE, 2),  // Delete 'c'
			array(PageSync_ArrayConversionGenerator::INSERT, 2, 'd'), // Insert 'd' after 'b'
			array(PageSync_ArrayConversionGenerator::CONVERT, 1, 'bb'), // Convert 'b' to 'bb'
			array(PageSync_ArrayConversionGenerator::INSERT, 1, 'x'), // Insert
			array(PageSync_ArrayConversionGenerator::CONVERT, 0, 'aa'), // Convert 'a' to 'aa'
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);
		$this->assertEquals($result, $desired);
	}
	
	public function testArray21()
	{
		$a = array(
			'a', 'x', 'b', 'c'
		);
		
		$b = array(
			'aa', 'bb', 'd'
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::DELETE, 3),  // Delete 'c'
			array(PageSync_ArrayConversionGenerator::INSERT, 3, 'd'), // Insert 'd' after 'b'
			array(PageSync_ArrayConversionGenerator::CONVERT, 2, 'bb'), // Convert 'b' to 'bb'
			array(PageSync_ArrayConversionGenerator::DELETE, 1), // Delete 'x'
			array(PageSync_ArrayConversionGenerator::CONVERT, 0, 'aa'), // Convert 'a' to 'aa'
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);
		$this->assertEquals($result, $desired);
	}
	
	public function testArray22()
	{
		$a = array(
			'W', 'a', 'b', 'c'
		);
		
		$b = array(
			'W', 'aa', 'x', 'bb', 'd'
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::DELETE, 3),  // Delete 'c'
			array(PageSync_ArrayConversionGenerator::INSERT, 3, 'd'), // Insert 'd' after 'b'
			array(PageSync_ArrayConversionGenerator::CONVERT, 2, 'bb'), // Convert 'b' to 'bb'
			array(PageSync_ArrayConversionGenerator::INSERT, 2, 'x'), // Insert
			array(PageSync_ArrayConversionGenerator::CONVERT, 1, 'aa'), // Convert 'a' to 'aa'
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);
		$this->assertEquals($result, $desired);
	}

	public function testArray23()
	{
		$a = array(
			'W', 'a', 'b', 'c'
		);
		
		$b = array(
			'W', 'aa', 'x', 'bb', 'd'
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::DELETE, 3),  // Delete 'c'
			array(PageSync_ArrayConversionGenerator::INSERT, 3, 'd'), // Insert 'd' after 'b'
			array(PageSync_ArrayConversionGenerator::CONVERT, 2, 'bb'), // Convert 'b' to 'bb'
			array(PageSync_ArrayConversionGenerator::INSERT, 2, 'x'), // Insert
			array(PageSync_ArrayConversionGenerator::CONVERT, 1, 'aa'), // Convert 'a' to 'aa'
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);
		$this->assertEquals($result, $desired);
	}
	
	public function testArray24()
	{
		$a = array(
			'a', 'b', 'c', 'd', 'e', 'f'
		);
		
		$b = array(
			'd', 'e', 'c', 'b', 'f', 'a'
		);
		
		$desired = array(
			array(PageSync_ArrayConversionGenerator::MOVE, 0, 6),
			array(PageSync_ArrayConversionGenerator::MOVE, 0, 2),
			array(PageSync_ArrayConversionGenerator::MOVE, 0, 4),
			array(PageSync_ArrayConversionGenerator::MOVE, 0, 4)
		);
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);
		
		$this->assertEquals($result, $desired);
	}
	
	public function testArray25()
	{
		//TODO: confirm this result is actually valid
		
		$a = array(
			'a', 'b', 'c', 'd', 'e', 'f', 'g',
			'a1', 'b1', 'c1', 'd1', 'e1', 'f1', 'g1',
			'a2', 'b2', 'c2', 'd2', 'e2', 'f2', 'g2',
			'a3', 'b3', 'c3', 'd3', 'e3', 'f3', 'g3',
		);
		
		$b = array(
			'a', 'a1', 'a2', 'a3',
			'b', 'b1', 'b2', 'b3',
			'c', 'c1', 'c2', 'c3',
			'd', 'd1', 'd2', 'd3',
			'e', 'e1', 'e2', 'e3',
			'f', 'f1', 'f2', 'f3',
			'g', 'g1', 'g2', 'g3',
		);
		
		$desired = 	array(
			array (
				0 => 1,
				1 => 1,
				2 => 22,
			),
			1 => 
			array (
				0 => 1,
				1 => 1,
				2 => 23,
			),
			2 => 
			array (
				0 => 1,
				1 => 1,
				2 => 24,
			),
			3 => 
			array (
				0 => 1,
				1 => 1,
				2 => 25,
			),
			4 => 
			array (
				0 => 1,
				1 => 1,
				2 => 26,
			),
			5 => 
			array (
				0 => 1,
				1 => 1,
				2 => 27,
			),
			6 => 
			array (
				0 => 1,
				1 => 2,
				2 => 17,
			),
			7 => 
			array (
				0 => 1,
				1 => 2,
				2 => 19,
			),
			8 => 
			array (
				0 => 1,
				1 => 2,
				2 => 21,
			),
			9 => 
			array (
				0 => 1,
				1 => 2,
				2 => 23,
			),
			10 => 
			array (
				0 => 1,
				1 => 2,
				2 => 25,
			),
			11 => 
			array (
				0 => 1,
				1 => 2,
				2 => 27,
			),
			12 => 
			array (
				0 => 1,
				1 => 3,
				2 => 12,
			),
			13 => 
			array (
				0 => 1,
				1 => 3,
				2 => 15,
			),
			14 => 
			array (
				0 => 1,
				1 => 3,
				2 => 18,
			),
			15 => 
			array (
				0 => 1,
				1 => 3,
				2 => 21,
			),
			16 => 
			array (
				0 => 1,
				1 => 3,
				2 => 24,
			),
			17 => 
			array (
				0 => 1,
				1 => 3,
				2 => 27,
			));
		
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);
		
		$this->assertEquals($result, $desired);
	}
	
	public function test26()
	{
		$a = array(
			1,
			2,
			22,
			222,
			2222,
			3,
			5
			);
		$b = array(
			3,
			1,
			5
			);
			
			
		$desired = array(
			0 => array(1, 0 , 6),
			1 => array(2, 3),
			2 => array(2, 2),
			3 => array(2, 1),
			4 => array(2, 0),
		);
			
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);
		$this->assertEquals($result, $desired);
	}
	
	public function test27()
	{
		$a = array(
			529,
			530,
			546,
			562,
			578,
			594,
			610,
			626,
			642,
			658,
			674,
			690,
			706,
			722,
			738,
			754,
			770);
		$b = array(
			529,
			674,
			690,
			706,
			722,
			738,
			754,
			770,
			530,
			546,
			562,
			578,
			594,
			610,
			626,
			642);
		$desired =  array( 0 =>
		  array (
		    0 => 1,
		    1 => 1,
		    2 => 17,
		  ),
		  1 =>
		  array (
		    0 => 1,
		    1 => 1,
		    2 => 17,
		  ),
		  2 =>
		  array (
		    0 => 1,
		    1 => 1,
		    2 => 17,
		  ),
		  3 =>
		  array (
		    0 => 1,
		    1 => 1,
		    2 => 17,
		  ),
		  4 =>
		  array (
		    0 => 1,
		    1 => 1,
		    2 => 17,
		  ),
		  5 =>
		  array (
		    0 => 1,
		    1 => 1,
		    2 => 17,
		  ),
		  6 =>
		  array (
		    0 => 1,
		    1 => 1,
		    2 => 17,
		  ),
		  7 =>
		  array (
		    0 => 1,
		    1 => 1,
		    2 => 17,
		  ),
		  8 =>
		  array (
		    0 => 2,
		    1 => 1,
		  ),
		);
		$result = PageSync_ArrayConversionGenerator::generateConversion($a, $b, self::$equalsFunction, self::$canBeConvertedFunction);
		$this->assertEquals($result, $desired);
	}
}
