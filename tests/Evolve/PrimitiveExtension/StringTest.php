<?php

namespace Phalcon\Evolve\Test\PrimitiveExtension;

use Phalcon\Evolve\PrimitiveExtension\StringExtension as Sx;
use Phalcon\Evolve\Test\UnitTestCase;


class StringTest extends UnitTestCase {

	public function test()
	{
		$str = Sx::x("This is a test string");
		$str_short = Sx::x("Test");
		$str_untrimmed = Sx::x("\t$str ");
		$str_number = Sx::x("24");
		
		$this->assertEquals(21, $str->length());
		
		$this->assertEquals("test", $str->slice(10, 4));
		
		$this->assertEquals("This is the test string", $str->replaceSlice('the', 8, 1));
		
		$this->assertEquals(10, $str->indexOf('test'));
		$this->assertFalse($str->indexOf('Test'));
		
		$this->assertEquals(16, $str->lastIndexOf('t'));
		$this->assertFalse($str->lastIndexOf('Test'));
		
		$this->assertTrue($str->contains('test'));
		$this->assertFalse($str->contains('Test'));
		
		$this->assertTrue($str->startsWith('This'));
		$this->assertFalse($str->startsWith('this'));
		
		$this->assertTrue($str->endsWith('string'));
		$this->assertFalse($str->endsWith('String'));
		
		$this->assertEquals(3, $str->count('i'));
		
		$this->assertEquals('Thos os a test strong', $str->replace('i', 'o'));
		
		$this->assertCount(5, $str->split(' '));
		
		$this->assertCount(11, $str->chunk(2));
		
		$this->assertEquals('TestTestTest', $str_short->repeat(3));
		
		$this->assertEquals('tseT', $str_short->reverse());
		
		$this->assertEquals('test', $str_short->toLower());
		
		$this->assertEquals('TEST', $str_short->toUpper());
		
		$this->assertEquals($str, $str_untrimmed->trim());
		
		$this->assertEquals("$str ", $str_untrimmed->trimLeft());
		
		$this->assertEquals("\t$str", $str_untrimmed->trimRight());
		
		$this->assertEquals("00024", $str_number->padLeft(5, '0'));
		
		$this->assertEquals("24   ", $str_number->padRight(5));
		
		$this->assertEquals("a test string", $str->getAfter(' is '));

		$this->assertTrue($str->equals("This is a test string"));
		$this->assertTrue($str->equals($str));
		$this->assertTrue($str->equals(Sx::x("This is a test string")));
		$this->assertFalse($str->equals($str_short));

	}
	
	public function testIgnoreCase()
	{
		$str = Sx::x("This is a test string");

		$this->assertEquals(10, $str->indexOf('Test', null, true));

		$this->assertEquals(16, $str->lastIndexOf('T', null, true));

		$this->assertTrue($str->contains('Test', true));

		$this->assertTrue($str->startsWith('this', true));

		$this->assertTrue($str->endsWith('String', true));

		$this->assertEquals(3, $str->count('I', 0, null, true));

		$this->assertEquals('Thos os a test strong', $str->replace("I", "o", null, true));
	}

	public function testConvertCase()
	{
		$str = Sx::x("App\\Controllers\\AdminApiController");

		$this->assertEquals("admin_api", $str->replace('Controller')->classNameToSnake());
	}

	public function testCompatible()
	{
		$this->assertEquals(strtotime('2015/01/01'), strtotime(Sx::x('2015/01/01')));
	}

	public function testRandom()
	{
		$str = Sx::random(14);
		$this->assertEquals(14, strlen($str));

		$str_x = Sx::random_x(23);
		$this->assertEquals(23, $str_x->length());
	}

	public function testIsYes()
	{
		$this->assertTrue(Sx::x("有り")->isYes());
		$this->assertTrue(Sx::x("Yes")->isYes());
		$this->assertTrue(Sx::x("ON")->isYes());
		$this->assertTrue(Sx::x("True")->isYes());
		$this->assertTrue(Sx::x("O")->isYes());

		$this->assertFalse(Sx::x("Off")->isYes());
		$this->assertFalse(Sx::x("false")->isYes());
		$this->assertFalse(Sx::x("x")->isYes());
		$this->assertFalse(Sx::x("Off")->isYes());

		$this->assertTrue(Sx::x("表示あり")->isYes(["表示", "削除"]));
		$this->assertTrue(Sx::x("削除する")->isYes(["表示", "削除"]));
		$this->assertFalse(Sx::x("表示しない")->isYes(["表示", "削除"]));
		$this->assertFalse(Sx::x("申請する")->isYes(["表示", "削除"]));
	}
	
} 