<?php


namespace Phalcon\Evolve\Collection;


interface Set extends \IteratorAggregate
{

    /**
     * @param $element
     */
    public function add($element);

    /**
     * @param Set $other
     * @return Set
     */
    public function diff(Set $other);

    /**
     * @param Set $other
     * @return Set
     */
    public function intersect(Set $other);

    /**
     * @param Set $other
     * @return Set
     */
    public function union(Set $other);

    /**
     * @return mixed[]
     */
    public function toArray();

}