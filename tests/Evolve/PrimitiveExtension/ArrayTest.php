<?php

namespace Phalcon\Evolve\Test\PrimitiveExtension;

use Phalcon\Evolve\PrimitiveExtension\ArrayExtension as Ax;
use Phalcon\Evolve\PrimitiveExtension\StringExtension as Sx;


class ArrayTest extends \UnitTestCase {

	public function testArrayAccess()
	{
		$arr = Ax::x([]);
		$this->assertEmpty($arr);
		$arr[] = "hello";
		$this->assertNotEmpty($arr);
		$arr[] = "world";
		$arr[0] = "new";
		$this->assertCount(2, $arr);
		list ($a, $b) = $arr;
		$this->assertEquals('new', $a);
		$this->assertEquals('world', $b);
	}

	public function testIterate()
	{
		$arr = Ax::x([
			"a" => "hello",
			"b" => "primitive",
			"c" => "world",
			6 => "hey!",
		]);
		$str = "";
		foreach ($arr as $key => $item) {
			$str .= "$key:$item ";
		}
		$this->assertEquals("a:hello b:primitive c:world 6:hey! ", $str);
	}

	public function testTraverse()
	{
		$arr = Ax::x(['zeroth', 'first', 'second', 'third', 'fourth']);
		$arr_empty = Ax::x([]);

		$this->assertTrue($arr->any());
		$this->assertFalse($arr_empty->any());
		$this->assertFalse($arr->isEmpty());
		$this->assertTrue($arr_empty->isEmpty());
		$this->assertEquals('zeroth', $arr->first());
		$this->assertEquals('0', $arr_empty->firstOrElse('0'));
		$this->assertEquals('fourth', $arr->last());
		$this->assertEquals('0', $arr_empty->lastOrElse('0'));
		$this->assertEquals(2,
			$arr->filter(function($item) { return Sx::x($item)->endsWith('th'); })
				->length()
		);
		
		$tree = Ax::x([
			'a' => [
				'a-a' => [
					'a-a-a' => 'zeroth',
				],
			]
		]);
		$this->assertEquals('zeroth', $tree->traverse(['a', 'a-a', 'a-a-a']));
		$this->assertNull($tree->traverse(['a', 'a-a', 'a-a-b']));
		$this->assertNull($tree->traverse(['a', 'a-b', 'a-a-a']));
		$this->assertEquals('default', $tree->traverse(['a', 'a-b'], 'default'));
	}
	
	public function testSearch()
	{
		$arr = Ax::x(['zeroth', 'first', 'second', 'third', 'fourth']);
		
		$this->assertTrue($arr->contains('first'));
		$this->assertFalse($arr->contains('fifth'));
		$this->assertEquals(3, $arr->indexOf('third'));
		$this->assertFalse($arr->indexOf('fifth'));
	}
	
	public function testManipulate()
	{
		$arr = Ax::x(['zeroth', 'fist', 'second', 'third', 'fourth']);
		
		$this->assertCount(6, $arr->pushImmutable('fifth'));
		$this->assertCount(8, $arr->pushRangeImmutable(['fifth', 'sixth', 'seventh']));
		$this->assertCount(4, $arr->removeImmutable('zeroth'));

		$this->assertCount(6, $arr->push('fifth'));
		$this->assertCount(9, $arr->pushRange(['fifth', 'sixth', 'seventh']));
		$this->assertCount(8, $arr->remove('zeroth'));
	}

	public function testKeyFilter()
	{
		$arr = Ax::x(['zeroth' => 0, 'first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4]);
		
		$this->assertEquals([], $arr->keyFilter([])->unwrap());
		$this->assertEquals([ 'second' => 2, 'third' => 3 ], $arr->keyFilter(['second', 'third'])->unwrap());
	}
} 