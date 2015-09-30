<?php
/**
 * Author: Kenta Suzuki
 * Since: 2014/10/22 15:24
 * Copyright: 2014 sukobuto.com All Rights Reserved.
 */

namespace Phalcon\Evolve\Security\Filter;

/**
 * Class DateHash
 * @package app\components\Security\Filter
 */
class DateHash {

	public function filter($value)
	{
		if (is_numeric($value)) return intval($value);
		if (!is_array($value)) return null;
		return $value;
	}

} 