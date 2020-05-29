<?php

namespace Phalcon\Evolve\Test\Collection;


use Phalcon\Evolve\Test\UnitTestCase;
use Phalcon\Evolve\Collection\StringHashSet;

class StringHashSetTest extends UnitTestCase {

	public function testEmpty()
	{
	    $set = new StringHashSet();
	    $this->assertEquals(0, count($set));
	    $this->assertEquals([], $set->toArray());
	}

    public function testInitialElements()
    {
        $set = new StringHashSet(["apple", "banana", "chocolate", "banana", "apple"]);
        $this->assertEquals(3, count($set));
        $actual = $set->toArray();
        sort($actual);
        $this->assertEquals(["apple", "banana", "chocolate"], $actual);
	}

    public function testDiff()
    {
        $set = new StringHashSet(["apple", "banana", "chocolate"]);
        $diff = $set->diff(new StringHashSet(["banana"]));

        $this->assertEquals(2, count($diff));
        $actual = $diff->toArray();
        sort($actual);
        $this->assertEquals(["apple", "chocolate"], $actual);
    }

    public function testIntersect()
    {
        $set = new StringHashSet(["apple", "banana", "chocolate"]);
        $intersect = $set->intersect(new StringHashSet(["apple", "chocolate", "donut"]));

        $this->assertEquals(2, count($intersect));
        $actual = $intersect->toArray();
        sort($actual);
        $this->assertEquals(["apple", "chocolate"], $actual);
    }

    public function testUnion()
    {
        $set = new StringHashSet(["apple", "banana", "chocolate"]);
        $union = $set->union(new StringHashSet(["apple", "chocolate", "donut"]));

        $this->assertEquals(4, count($union));
        $actual = $union->toArray();
        sort($actual);
        $this->assertEquals(["apple", "banana", "chocolate", "donut"], $actual);
    }

} 