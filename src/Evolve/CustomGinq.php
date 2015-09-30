<?php

namespace Phalcon\Evolve;

use Ginq\Ginq;
use Phalcon\Evolve\PrimitiveExtension\ArrayExtension as Ax;
use Phalcon\Evolve\PrimitiveExtension\StringExtension as Sx;

class CustomGinq extends Ginq
{

	/**
	 * @param string $glue
	 * @param callable|array|string|null $stringSelector (v, k) -> string
	 * @return string
	 */
	public function implode($glue, $stringSelector = null)
	{
		$items = $this->select($stringSelector)->toList();
		return implode($glue, $items);
	}

	/**
	 * @param string $glue
	 * @param callable|array|string|null $stringSelector (v, k) -> string
	 * @return Sx
	 */
	public function implode_x($glue, $stringSelector = null)
	{
		return Sx::x($this->implode($glue, $stringSelector));
	}

	/**
	 * @return Ax
	 */
	public function toArrayExtension()
	{
		return Ax::x($this);
	}

}