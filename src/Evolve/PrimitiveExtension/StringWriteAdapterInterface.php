<?php

namespace Phalcon\Evolve\PrimitiveExtension;


interface StringWriteAdapterInterface
{
	/**
	 * @param $string
	 */
	public function write($string);
}