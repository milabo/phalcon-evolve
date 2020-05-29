<?php


namespace Phalcon\Evolve\Collection;


/**
 * Class StringHashSet
 * 集合
 * 連想配列のキーで実現するため要素には文字列しか使えないため StringHashSet という名前にした
 *
 * @package Phalcon\Evolve\System
 */
class StringHashSet implements \Countable, Set
{

    private $elements = [];

    /**
     * StringHashSet constructor.
     * @param string[] $elements
     */
    public function __construct($elements = [])
    {
        foreach ($elements as $element) {
            if (!is_string($element)) {
                throw new \InvalidArgumentException("element of StringHashSet should be string.");
            }
            $this->elements[$element] = true;
        }
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        return new \ArrayIterator(array_keys($this->elements));
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return count($this->elements);
    }

    /**
     * @param string $element
     */
    public function add($element)
    {
        if (!is_string($element)) {
            throw new \InvalidArgumentException("element of StringHashSet should be string.");
        }
        $this->elements[$element] = true;
    }

    public function diff(Set $other)
    {
        return new self(array_diff($this->toArray(), $other->toArray()));
    }

    public function intersect(Set $other)
    {
        return new self(array_intersect($this->toArray(), $other->toArray()));
    }

    public function union(Set $other)
    {
        return new self(array_merge($this->toArray(), $other->toArray()));
    }

    public function toArray()
    {
        return iterator_to_array($this);
    }
}