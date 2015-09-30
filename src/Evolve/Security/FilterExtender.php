<?php
/**
 * Author: Kenta Suzuki
 * Since: 2014/10/21 15:18
 * Copyright: 2014 sukobuto.com All Rights Reserved.
 */

namespace Phalcon\Evolve\Security;

use Phalcon\Filter;
use Phalcon\Evolve\PrimitiveExtension\ArrayExtension as Ax;

/**
 * フレームワークに独自フィルタを追加する拡張
 * @package Phalcon\Evolve\Security
 */
class FilterExtender {

	public static function stringArray($value)
	{
		if (!is_array($value)) return [];
		return Ax::x($value)->map(function($item) {
			return strval($item);
		});
	}
	
	public static function integerArray($value)
	{
		if (!is_array($value)) return [];
		return Ax::x($value)->map(function($item) {
			return intval($item);
		});
	}

	public static function dateHash($value)
	{
		if (!is_array($value)) return null;
		$y = $value['year'] = intval($value['year']);
		$m = $value['month'] = intval($value['month']);
		$d = $value['day'] = intval($value['day']);
		if (!checkdate($m, $d, $y)) {
			throw new \ErrorException("妥当ではない日付 $y/$m/$d が入力されました。");
		}
		return $value;
	}

	public static function dateHashArray($value)
	{
		if (!is_array($value)) return [];
		return Ax::x($value)->map(function($item) {
			return self::dateHash($item);
		});
	}

	public static function register(Filter $filter)
	{
		$fe = get_class();
		$filter
			->add('array<string>', "$fe::stringArray")
			->add('array<int>', "$fe::integerArray")
			->add('date_hash', "$fe::dateHash")
			->add('array<date_hash>', "$fe::dateHashArray")
		;
	}

}