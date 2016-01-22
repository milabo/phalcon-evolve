<?php

namespace Phalcon\Evolve\View;

use Phalcon\Evolve\PrimitiveExtension\ArrayExtension;
use Phalcon\Evolve\CustomGinq as Ginq;
use Phalcon\DI;
use Phalcon\Evolve\PrimitiveExtension\ArrayExtension as Ax;

class VoltExtender {
	
	/**
	 * Volt filter yen
	 *
	 * @param integer
	 * @return string
	 */
	public static function yen($argument)
	{
		return '\\' . number_format($argument);
	}

	public static function lf($argument)
	{
		return $argument . "\n";
	}

	public static function cr($argument)
	{
		return $argument . "\r";
	}

	public static function crlf($argument)
	{
		return $argument . "\r\n";
	}

	/**
	 * Trim width
	 * @param string $resolved
	 * @param integer $length
	 * @param string $trimmarker
	 * @return string
	 */
	public static function tw($resolved, $length, $trimmarker = '…')
	{
		$len = mb_strwidth($resolved, 'utf-8');
		$tighter = function($str) use($len) {
			return '<span style="letter-spacing: -1px;" data-strwidth="'. $len .'">' . $str . '</span>';
		};
		switch(true) {
			case $len <= $length:
				return $resolved;
			case $len <= ($length +4):
				return $tighter($resolved);
			default:
				return $tighter(mb_strimwidth($resolved, 0, $length, $trimmarker, 'UTF-8'));
		}
	}

	/**
	 * New line to BR
	 * @param $argument
	 * @return mixed
	 * @author ${MY_NAME}
	 */
	public static function nlbr($argument)
	{
		$argument = str_replace(array("<!--nobr-->\r\n", "<!--nobr-->\r", "<!--nobr-->\n"), '', $argument);
		return str_replace(array("\r\n", "\r", "\n"), '<br/>', $argument);
	}

	/**
	 * @param string $resolved
	 * @param string $text
	 * @return string
	 */
	public static function url2link($resolved, $text)
	{
		$pattern = '/(href="|\]\()?https?:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:@&=+$,%#]+/';
		$resolved = preg_replace_callback($pattern, function($matches) use ($text) {
			// 既にリンクの場合や Markdown style link の場合はそのまま
			if (isset($matches[1])) return $matches[0];
			return "<a href=\"{$matches[0]}\">$text</a>";
		}, $resolved);
		return $resolved;
	}

	/**
	 * @param string $resolved
	 * @return string
	 */
	public static function mdLink($resolved)
	{
		// 電話番号, メールアドレスリンク
		$pattern = '/\[(tel|mailto):(.+)\]/';
		$resolved = preg_replace_callback($pattern, function($matches) {
			$scheme = $matches[1];
			$value = $matches[2];
			return '<a href="' . $scheme . ':' . $value . '">' . $value . '</a>';
		}, $resolved);

		// Markdown スタイルリンク
		$pattern = '/\[([^\]]+)\]\(([^\)]*)\)/';
		$resolved = preg_replace_callback($pattern, function($matches) {
			$title = $matches[1];
			$url = $matches[2];
			return '<a href="' . $url . '">' . $title . '</a>';
		}, $resolved);

		return $resolved;
	}

	public static function emptyTo($resolved, $empty_mark)
	{
		return empty($resolved) ? $empty_mark : $resolved; 
	}

	/**
	 * @param string|integer $resolved
	 * @param string $format
	 * @return string
	 */
	public static function dateFormat($resolved, $format)
	{
		if (is_numeric($resolved)) {
			$resolved = intval($resolved);
		} else {
			$resolved = strtotime($resolved);
		}
		return date($format, $resolved);
	}

	/**
	 * @param string|integer $resolved
	 * @param array $map
	 * @return
	 */
	public static function map($resolved, $map)
	{
		if (isset($map[$resolved])) {
			return $map[$resolved];
		}
		return $resolved;
	}

	/**
	 * 現在のクエリストリングを返却します
	 * 引数 $alternate に変更・追加したいパラメータを指定できます
	 *
	 * @param array $alternate
	 * @return string
	 */
	public static function queryString($alternate = array())
	{
		$q = $_GET; // 配列コピー (PHPでは代入で複製できる)
		$q = array_merge($q, $alternate);
		unset($q['_url']);
		unset($q['_city']);
		if (Ax::x($q)->isEmpty()) return '';
		$pairs = Ax::x([]);
		foreach ($q as $key => $value) $pairs[] = "$key=$value";
		return '?' . $pairs->join('&');
	}

	public static function getOrDefault($ref, $default = null)
	{
		if (isset($ref)) {
			if (isset($prefix)) $ref = $prefix . $ref;
			if (isset($suffix)) $ref .= $suffix;
			return $ref;
		}
		return $default;
	}

	/**
	 * @param array $array
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function searchOrDefault($array, $key, $default)
	{
		return array_key_exists($key, $array) ? $array[$key] : $default;
	}

	/**
	 * @param bool $selected
	 * @return string
	 */
	public static function selectedWhen($selected)
	{
		return $selected ? ' selected' : '';
	}

	/**
	 * @param bool $checked
	 * @return string
	 */
	public static function checkedWhen($checked)
	{
		return $checked ? ' checked' : '';
	}

	/**
	 * @param bool $disabled
	 * @return string
	 */
	public static function disabledWhen($disabled)
	{
		return $disabled ? ' disabled' : '';
	}

	/**
	 * チェックボックス要素のための属性を出力する
	 * @param string $name フィールド名
	 * @param string $value 値
	 * @param array $container この配列に値が含まれていればチェック済みにする
	 * @return string name="$name"[ value="$value" [checked]]
	 */
	public static function checkboxAttributes($name, $value = null, $container = [])
	{
		$acc = Ax::x($container)->contains($value) ? ' checked' : '';
		$value = $value ? (' value="' . $value . '"' . $acc) : '';
		return 'type="checkbox" name="' . $name . '"' . $value;
	}

	public static function noop()
	{
		return '';
	}

	public static function jpWeekday($date = null)
	{
		$time = DI::getDefault()->get('clock')->nowTs();
		if (!is_null($date)) {
			if (is_integer($date)) {
				$time = $date;
			} else if (is_string($date)) {
				$time = strtotime($date);
			} else if ($date instanceof \DateTime) {
				$time = $date->getTimestamp();
			}
		}
		$weekdays = ['日', '月', '火', '水', '木', '金', '土'];
		return $weekdays[intval(date('w', $time))];
	}

	public static function from($seq)
	{
		if ($seq instanceof Ginq) return $seq;
		if (is_array($seq)) return Ginq::from($seq);
		$list = [];
		foreach ($seq as $result) {
			$list[] = $result;
		}
		return Ginq::from($list);
	}

	public static function ax($array)
	{
		return ArrayExtension::x($array);
	}

	/**
	 * @param \Phalcon\Security $security
	 * @return string
	 */
	public static function formToken($security)
	{
		static $elm = null;
		if (!$elm) {
			$key = $security->getTokenKey();
			$value = $security->getToken();
			$elm = "<input type=\"hidden\" name=\"{$key}\" value=\"{$value}\" />";
		}
		return $elm;
	}

	/**
	 * Volt コンパイラに拡張機能を登録
	 * 
	 * @param \Phalcon\Mvc\View\Engine\Volt\Compiler $compiler
	 */
	public static function register($compiler)
	{
		$ve = get_class();
		$compiler
			->addFilter('number', "number_format")
			->addFilter('yen', "$ve::yen")
			->addFilter('lf', "$ve::lf")
			->addFilter('cr', "$ve::cr")
			->addFilter('crlf', "$ve::crlf")
			->addFilter('tw', "$ve::tw")
			->addFilter('nlbr', "$ve::nlbr")
			->addFilter('url2link', "$ve::url2link")
			->addFilter('mdlink', "$ve::mdLink")
			->addFilter('empty_to', "$ve::emptyTo")
			->addFilter('date_format', "$ve::dateFormat")
			->addFilter('map', "$ve::map")
			->addFunction('render', "$ve::render")
			->addFunction('query_string', "$ve::queryString")
			->addFunction('get_or_default', "$ve::getOrDefault")
			->addFunction('range', 'range')
			->addFunction('var_export', 'var_export')
			->addFunction('search_or_default', "$ve::searchOrDefault")
			->addFunction('selected_when', "$ve::selectedWhen")
			->addFunction('checked_when', "$ve::checkedWhen")
			->addFunction('disabled_when', "$ve::disabledWhen")
			->addFunction('checkbox_attributes', "$ve::checkboxAttributes")
			->addFunction('t', "\$this->translate->query")
			->addFunction('d', "\$this->translate->dic")
			->addFunction('jp_weekday', "$ve::jpWeekday")
			->addFunction('from', "$ve::from")
			->addFunction('ax', "$ve::ax")
			->addFunction('form_token', "$ve::formToken")
		;
	}

} 