<?php
/**
 * Author: Kenta Suzuki
 * Since: 2014/10/22 15:24
 * Copyright: 2014 sukobuto.com All Rights Reserved.
 */

namespace Phalcon\Evolve\Security\Filter;

/**
 * 郵便番号や電話番号などの区切り文字を除去するフィルタ
 * @package app\components\Security\Filter
 */
class SeparatedNumber {

	public function filter($value)
	{
		$value = mb_convert_kana($value, "a");
		return str_replace([
			'—',
			'-',
			'-',
			'‐',
			'-',
			'‑',
			'_',
			','
		], '', $value);
	}

} 