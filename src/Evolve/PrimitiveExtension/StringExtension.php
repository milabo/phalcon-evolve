<?php

namespace Phalcon\Evolve\PrimitiveExtension;

/**
 * Class StringExtension
 * @package Phalcon\Evolve\PrimitiveExtension
 */
class StringExtension {

	private $true_signs_startswith = [
		"y", "on", "true", "1",
		"有", "あり", "する", "はい", "○", "◯",
	];
	private $true_signs_equals = [
		"o",
	];

	/** @var string */
	private $string;

	/**
	 * 文字列を拡張する
	 * @param string $string
	 * @return StringExtension
	 */
	public static function x($string)
	{
		return new self($string);
	}

	/**
	 * 桁数を指定してランダム文字列を生成する
	 * @param integer $length
	 * @return string
	 */
	public static function random($length)
	{
		$str = array_merge(range('a', 'z'), range('0', '9'), range('A', 'Z'));
		$r_str = null;
		for ($i = 0; $i < $length; $i++) {
			$r_str .= $str[rand(0, count($str) - 1)];
		}
		return $r_str;
	}

	/**
	 * 桁数を指定してランダム文字列を生成する
	 * @param integer $length
	 * @return StringExtension
	 */
	public static function random_x($length)
	{
		return new self(self::random($length));
	}

	/**
	 * 文字列を拡張するコンバータを取得する
	 * Ginq::map 等で利用
	 * @return \Closure
	 */
	public static function getConverter()
	{
		return function($string) {
			return self::x($string);
		};
	}

	public static function eq($a, $b)
	{
		return strval($a) === strval($b);
	}

	public function __construct($string)
	{
		$this->string = strval($string);
	}

	public function __toString()
	{
		return $this->string;
	}

	public function write(StringWriteAdapterInterface $adapter = null)
	{
		if ($adapter) {
			$adapter->write($this->string);
		} else {
			echo $this->string;
		}
		return $this;
	}

	/**
	 * 拡張を解く
	 * @return string
	 */
	public function unwrap()
	{
		return $this->string;
	}

	/**
	 * @return int
	 */
	public function length() {
		return strlen($this->string);
	}

	public function isEmpty()
	{
		return $this->length() === 0;
	}

	/**
	 * @param string|mixed $string
	 * @param bool $ignore_case
	 * @return bool
	 */
	public function equals($string, $ignore_case = false)
	{
		$a = $this->string;
		$b = strval($string);
		if ($ignore_case) {
			$a = strtolower($a);
			$b = strtolower($b);
		}
		return $a === $b;
	}

	/**
	 * @param array $verbs
	 * @return bool
	 */
	public function isYes($verbs = [])
	{
		// 動詞そのものなら true として扱う
		// 例「表示」「削除」
		if ($this->in($verbs)) {
			return true;
		}
		// 動詞を除いたのこりが true_signs に該当するか
		$remain = self::x(str_replace($verbs, "", $this->string));
		foreach ($this->true_signs_startswith as $sign) {
			if ($remain->startsWith($sign, true)) {
				return true;
			}
		}
		foreach ($this->true_signs_equals as $sign) {
			if ($remain->equals($sign, true)) {
				return true;
			}
		}
		return false;
	}

	/*
	 * Slicing methods
	 */

	/**
	 * @param int $offset
	 * @param int $length
	 * @return StringExtension
	 */
	public function slice($offset, $length = null) {
		$offset = $this->prepareOffset($offset);
		$length = $this->prepareLength($offset, $length);

		if (0 === $length) {
			return self::x('');
		}

		return self::x(substr($this->string, $offset, $length));
	}

	/**
	 * @param string $replacement
	 * @param int $offset
	 * @param int $length
	 * @return StringExtension
	 */
	public function replaceSlice($replacement, $offset, $length = null) {
		$offset = $this->prepareOffset($offset);
		$length = $this->prepareLength($offset, $length);

		return self::x(substr_replace($this->string, $replacement, $offset, $length));
	}

	/*
	 * Search methods
	 */

	/**
	 * @param string $string
	 * @param int $offset
	 * @param bool|false $ignore_case
	 * @return bool|int
	 */
	public function indexOf($string, $offset = 0, $ignore_case = false) {
		$string = strval($string);
		$offset = $this->prepareOffset($offset);

		if ('' === $string) {
			return $offset;
		}

		list($self, $string) = $this->treatCaseGap($ignore_case, $this->string, $string);

		return strpos($self, $string, $offset);
	}

	/**
	 * @param string $string
	 * @param int $offset
	 * @param bool|false $ignore_case
	 * @return bool|int
	 */
	public function lastIndexOf($string, $offset = null, $ignore_case = false) {
		$string = strval($string);
		if (null === $offset) {
			$offset = $this->length();
		} else {
			$offset = $this->prepareOffset($offset);
		}

		if ('' === $string) {
			return $offset;
		}
		
		list($self, $string) = $this->treatCaseGap($ignore_case, $this->string, $string);
		
		/* Converts $offset to a negative offset as strrpos has a different
		 * behavior for positive offsets. */
		return strrpos($self, $string, $offset - $this->length());
	}

	/**
	 * @param string $string
	 * @param bool|false $ignore_case
	 * @return bool
	 */
	public function contains($string, $ignore_case = false) {
		$string = strval($string);
		return false !== $this->indexOf($string, null, $ignore_case);
	}

	/**
	 * @param string|array $string
	 * @param bool $ignore_case
	 * @return bool
	 */
	public function startsWith($string, $ignore_case = false) {
		if (is_array($string)) {
			foreach ($string as $key) {
				if ($this->startsWith($key)) return true;
			}
			return false;
		}
		return 0 === $this->indexOf($string, null, $ignore_case);
	}

	/**
	 * @param string $string
	 * @param bool $ignore_case
	 * @return bool
	 */
	public function endsWith($string, $ignore_case = false) {
		$string = strval($string);
		return $this->lastIndexOf($string, null, $ignore_case)
				=== $this->length() - strlen($string);
	}

	/**
	 * @param $string
	 * @param int $offset
	 * @param null $length
	 * @param bool|false $ignore_case
	 * @return int
	 */
	public function count($string, $offset = 0, $length = null, $ignore_case = false) {
		$string = strval($string);
		$offset = $this->prepareOffset($offset);
		$length = $this->prepareLength($offset, $length);

		if ('' === $string) {
			return $length + 1;
		}
		
		list($self, $string) = $this->treatCaseGap($ignore_case, $this->string, $string);

		return substr_count($self, $string, $offset, $length);
	}

	/**
	 * This function has two prototypes:
	 *
	 * replace(array(string $from => string $to) $replacements, int $limit = PHP_MAX_INT)
	 * replace(string $from, string $to, int $limit = PHP_MAX_INT)
	 * 
	 * @param string $from
	 * @param string $to
	 * @param int $limit
	 * @param bool $ignore_case
	 * @return self
	 */
	public function replace($from, $to = null, $limit = null, $ignore_case = false) {
		$from = $this->treatCaseGap($ignore_case, $from);
		if (is_array($from)) {
			$replacements = $from;
			$limit = $to;

			$this->verifyNotContainsEmptyString(
				array_keys($replacements), 'Replacement array keys'
			);

			if (null === $limit) {
				return self::x(strtr($this->string, $from));
			} else {
				$this->verifyPositive($limit, 'Limit');
				return self::x($this->replaceWithLimit($this->string, $replacements, $limit, $ignore_case));
			}
		} else {
			$this->verifyNotEmptyString($from, 'From string');

			if (null === $limit) {
				return self::x(str_replace($from, $to, $this->string));
			} else {
				$this->verifyPositive($limit, 'Limit');
				return self::x($this->replaceWithLimit($this->string, [$from => $to], $limit, $ignore_case));
			}
		}
	}

	/**
	 * @param string $separator
	 * @param bool|false $remove_empties
	 * @return array
	 */
	public function split($separator, $remove_empties = false) {
		return $this->split_x($separator, $remove_empties)->unwrap();
	}

	/**
	 * @param string $separator
	 * @param bool|false $remove_empties
	 * @return ArrayExtension
	 */
	public function split_x($separator, $remove_empties = false)
	{
		$items = ArrayExtension::x(explode(strval($separator), $this->string));
		if ($remove_empties) {
			$items = $items->filter(function($item) { return !empty($item); });
		}
		return $items;
	}

	/**
	 * @param int $chunkLength
	 * @return StringExtension
	 */
	public function chunk($chunkLength = 1) {
		$this->verifyPositive($chunkLength, 'Chunk length');
		return str_split($this->string, $chunkLength);
	}

	/**
	 * @param $times
	 * @return StringExtension
	 */
	public function repeat($times) {
		$this->verifyNotNegative($times, 'Number of repetitions');
		return self::x(str_repeat($this->string, $times));
	}

	public function reverse() {
		return self::x(strrev($this->string));
	}

	public function toLower() {
		return self::x(strtolower($this->string));
	}

	public function toUpper() {
		return self::x(strtoupper($this->string));
	}

	public function trim($characters = " \t\n\r\v\0") {
		return self::x(trim($this->string, $characters));
	}

	public function trimLeft($characters = " \t\n\r\v\0") {
		return self::x(ltrim($this->string, $characters));
	}

	public function trimRight($characters = " \t\n\r\v\0") {
		return self::x(rtrim($this->string, $characters));
	}

	public function padLeft($length, $padString = " ") {
		return self::x(str_pad($this->string, $length, $padString, STR_PAD_LEFT));
	}

	public function padRight($length, $padString = " ") {
		return self::x(str_pad($this->string, $length, $padString, STR_PAD_RIGHT));
	}
	
	public function in($haystack) {
		return ArrayExtension::x($haystack)->contains($this->string);
	}
	
	public function toPascalCase($delim = '_')
	{
		$str = strtolower($this->string);
		$str = str_replace($delim, ' ', $str);
		$str = ucwords($str);
		$str = str_replace(' ', '', $str);
		return self::x($str);
	}
	
	public function toCamelCase($delim = '_')
	{
		$str = $this->toPascalCase($delim)->unwrap();
		$str[0] = strtolower($str[0]);
		return self::x($str);
	}

	public function toSnakeCase($delim = '_')
	{
		$str = preg_replace("/([A-Z])/", "{$delim}$1", $this->string);
		$str = strtolower($str);
		return self::x(ltrim($str, $delim));
	}

	public function baseClassName()
	{
		return self::x(basename($this->replace('\\', '/')));
	}

	/**
	 * クラス名から名前空間を除去しスネークケースへ変換する
	 * @param string $delim
	 * @return self
	 */
	public function classNameToSnake($delim = '_')
	{
		return $this->baseClassName()->toSnakeCase($delim);
	}

	/**
	 * @param string $str
	 * @return self
	 */
	public function getAfter($str)
	{
		$pos = $this->indexOf($str);
		return self::x(substr($this->string, $pos + strlen($str)));
	}

	protected function prepareOffset($offset) {
		if (!isset($offset)) $offset = 0;
		$len = $this->length();
		if ($offset < -$len || $offset > $len) {
			throw new \InvalidArgumentException('Offset must be in range [-len, len]');
		}

		if ($offset < 0) {
			$offset += $len;
		}

		return $offset;
	}

	protected function prepareLength($offset, $length) {
		if (null === $length) {
			return $this->length() - $offset;
		}

		if ($length < 0) {
			$length += $this->length() - $offset;

			if ($length < 0) {
				throw new \InvalidArgumentException('Length too small');
			}
		} else {
			if ($offset + $length > $this->length()) {
				throw new \InvalidArgumentException('Length too large');
			}
		}

		return $length;
	}

	protected function verifyPositive($value, $name) {
		if ($value <= 0) {
			throw new \InvalidArgumentException("$name has to be positive");
		}
	}

	protected function verifyNotNegative($value, $name) {
		if ($value < 0) {
			throw new \InvalidArgumentException("$name can not be negative");
		}
	}

	protected function verifyNotEmptyString($value, $name) {
		if ((string) $value === '') {
			throw new \InvalidArgumentException("$name can not be an empty string");
		}
	}

	protected function verifyNotContainsEmptyString(array $array, $name) {
		foreach ($array as $value) {
			if ((string) $value === '') {
				throw new \InvalidArgumentException("$name can not contain an empty string");
			}
		}
	}

	/* This effectively implements strtr with a limit */
	protected function replaceWithLimit($str, array $replacements, $limit, $ignore_case = false) {
		if (empty($replacements)) {
			return $str;
		}

		$this->sortKeysByStringLength($replacements);
		$regex = $this->createFromStringRegex($replacements, $ignore_case);

		return preg_replace_callback($regex, function($matches) use($replacements) {
			return $replacements[$matches[0]];
		}, $str, $limit);
	}

	protected function sortKeysByStringLength(array &$array) {
		var_dump($array);
		uksort($array, function($str1, $str2) {
			return strlen($str2) - strlen($str1);
		});
	}

	protected function createFromStringRegex(array $replacements, $ignore_case = false) {
		$fromRegexes = [];
		foreach ($replacements as $from => $_) {
			$fromRegexes[] = preg_quote($from, '~');
		}

		return '~(?:' . implode('|', $fromRegexes) . ')~S' . ($ignore_case ? 'i' : '');
	}

	/**
	 * @param $ignore_case
	 * @param $str
	 * @return string[]|string
	 */
	protected function treatCaseGap($ignore_case, $str) {
		if (func_num_args() > 2) {
			$ret = array();
			$src = func_get_args();
			for ($i = 1; $i < count($src); $i++) {
				$ret[] = $ignore_case ? $this->toLowerVar($src[$i]) : $src[$i];
			}
			return $ret;
		} else {
			if ($ignore_case) {
				$str = $this->toLowerVar($str);
			}
			return $str;
		}
	}

	/**
	 * @param string[]|string $str
	 * @return string[]|string
	 */
	protected function toLowerVar($str) {
		if (is_array($str)) {
			$ret = array();
			foreach ($str as $s) {
				$ret = strtolower($s);
			}
			return $ret;
		} else {
			return strtolower($str);
		}
	}
} 