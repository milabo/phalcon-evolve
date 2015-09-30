<?php
/**
 * Author: Kenta Suzuki
 * Since: 2014/10/22 15:24
 * Copyright: 2014 sukobuto.com All Rights Reserved.
 */

namespace Phalcon\Evolve\Security\Filter;

/**
 * 半角カタカナを全角カタカナに変換
 * @package app\components\Security\Filter
 */
class FullKana {

	public function filter($value)
	{
		return mb_convert_kana($value, "KV", 'Shift-JIS');
	}

} 