<?php
/**
 * Author: Kenta Suzuki
 * Since: 2014/10/22 15:24
 * Copyright: 2014 sukobuto.com All Rights Reserved.
 */

namespace Phalcon\Evolve\Security\Filter;

/**
 * なにもしないダミーフィルタ
 * @package app\components\Security\Filter
 */
class Nop {

	public function filter($value)
	{
		return $value;
	}

} 