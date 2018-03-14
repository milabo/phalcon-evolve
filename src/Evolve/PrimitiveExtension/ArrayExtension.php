<?php

namespace Phalcon\Evolve\PrimitiveExtension;

use Ginq\Ginq;
use Phalcon\Evolve\CustomGinq;

class ArrayExtension implements \Countable, \Iterator, \ArrayAccess {

	/** @type array */
	private $array;
	
	/** @type int */
	private $position = 0;

	/**
	 * 配列を拡張する
	 * @param array|self|\Ginq\Ginq|CustomGinq $array
	 * @return ArrayExtension
	 */
	public static function x($array)
	{
		if (is_array($array)) {
			return new self($array);
		}
		if ($array instanceof self) return $array;
		if ($array instanceof CustomGinq
			or (class_exists("Ginq\\Ginq") and $array instanceof \Ginq\Ginq)) {
			return new self($array->toArray());
		}
		throw new \InvalidArgumentException();
	}

	public static function zero()
	{
		return self::x([]);
	}

	/**
	 * 配列を拡張するコンバータを取得する
	 * Ginq::map 等で利用
	 * @return \Closure
	 */
	public static function getConverter()
	{
		return function($array) {
			return self::x($array);
		};
	}

	public function __construct(array $array)
	{
		$this->array = $array;
	}

	/**
	 * 拡張を解く
	 * @return array
	 */
	public function unwrap()
	{
		return $this->array;
	}

	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Count elements of an object
	 * @link http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 * </p>
	 * <p>
	 * The return value is cast to an integer.
	 */
	public function count()
	{
		return count($this->array);
	}

	public function rewind()
	{
		$this->position = 0;
	}

	public function current()
	{
		$array_keys = array_keys($this->array);
		return $this->array[$array_keys[$this->position]];
	}

	public function key()
	{
		$array_keys = array_keys($this->array);
		return $array_keys[$this->position];
	}

	public function next()
	{
		++$this->position;
	}

	public function valid()
	{
		$array_keys = array_keys($this->array);
		return isset($array_keys[$this->position]);
	}

	public function offsetSet($offset, $value)
	{
		if (is_null($offset)) {
			$this->array[] = $value;
		} else {
			$this->array[$offset] = $value;
		}
	}

	public function offsetExists($offset)
	{
		return isset($this->array[$offset]);
	}

	public function offsetUnset($offset)
	{
		unset($this->array[$offset]);
	}

	public function offsetGet($offset)
	{
		return isset($this->array[$offset]) ? $this->array[$offset] : null;
	}

	#region information

	public function length()
	{
		return count($this->array);
	}

	public function isEmpty()
	{
		return is_null(key($this->array));
	}

	public function any()
	{
		return !$this->isEmpty();
	}

	/**
	 * @param string $indent
	 * @return string
	 */
	public function toString($indent = "")
	{
		$str = "";
		foreach ($this->array as $key => $value) {
			$str .= "{$indent}{$key} : ";
			if ($value instanceof \Phalcon\Mvc\Model) {
				if (method_exists($value, "__toString")) {
					$str .= $value->__toString() . "\n";
				} else {
					$str .= "\n";
					$str .= self::x($value->toArray())->toString($indent . "  ");
				}
			} else if (is_array($value) or $value instanceof self or $value instanceof Ginq) {
				$value = self::x($value);
				$str .= $value->length() . " items\n";
				$str .= $value->toString($indent . "  ");
			} else if (is_integer($value)) {
				$str .= "{$value}\n";
			} else if (is_bool($value)){
				$str .= $value ? "true\n" : "false\n";
			} else {
				$str .= "\"{$value}\"\n";
			}
		}
		return $str;
	}

	/**
	 * @param string $indent
	 * @return string
	 */
	public function toCode($indent = "\t")
	{
		$str = "[\n";
		foreach ($this->array as $key => $value) {
			$str .= "{$indent}'{$key}' => ";
			if (is_array($value)) {
				$str .= self::x($value)->toCode($indent . "\t") . ",\n";
			} else if (is_integer($value)) {
				$str .= "{$value},\n";
			} else if (is_bool($value)){
				$str .= $value ? "true,\n" : "false,\n";
			} else {
				$str .= "\"{$value}\",\n";
			}
		}
		return $str . StringExtension::x($indent)->slice(1) . "]";
	}

	/**
	 * 配列を1行のCSVに変換して返す
	 * 文字列はすべて enclosure で囲む
	 * @param string $delimiter
	 * @param string $enclosure
	 * @return string
	 */
	public function toCsvLine($delimiter = ',', $enclosure = '"')
	{
		$escape = function($value) use ($enclosure) {
			switch (true) {
				case is_null($value): return "";
				case is_integer($value) or is_float($value): return $value;
			}
			$value = str_replace($enclosure, "{$enclosure}{$enclosure}", $value);
			return "{$enclosure}{$value}{$enclosure}";
		};
		return $this->map($escape)->join($delimiter);
	}

	/**
	 * 配列を1行のCSVに変換して返す
	 * 文字列はすべて enclosure で囲む
	 * @param string $delimiter
	 * @param string $enclosure
	 * @return StringExtension
	 */
	public function toCsvLine_x($delimiter = ',', $enclosure = '"')
	{
		return StringExtension::x($this->toCsvLine($delimiter, $enclosure));
	}
	
	#endregion
	
	#region traverse

	public function each($callback)
	{
		foreach ($this->array as $key => $item) $callback($item, $key);
		return $this;
	}

	/**
	 * @param $callback
	 * @return ArrayExtension
	 */
	public function map($callback)
	{
		// intval は第二引数が基数なので 第二引数に index を渡さない array_map を使う
		if ($callback === 'intval') {
			return self::x(array_map('intval', $this->array));
		}
		$nop = (new \ReflectionFunction($callback))->getNumberOfParameters();
		$ret = [];
		if ($nop == 0) {
			foreach ($this->array as $key => $value) {
				$ret[$key] = call_user_func($callback);
			}
		}
		if ($nop == 1) {
			foreach ($this->array as $key => $value) {
				$ret[$key] = call_user_func($callback, $value);
			}
		} else {
			foreach ($this->array as $key => $value) {
				$ret[$key] = call_user_func($callback, $value, $key);
			}
		}
		return self::x($ret);
	}
	
	public function toList()
	{
		return array_values($this->array);
	}
	
	public function toDictionary($keySelector, $valueSelector = null)
	{
		$dict = [];
		foreach ($this->array as $item) {
			$dict[call_user_func($keySelector, $item)]
				= isset($valueSelector)
				? call_user_func($valueSelector, $item)
				: $item;
		}
		return $dict;
	}

	public function keys()
	{
		return self::x(array_keys($this->array));
	}

	public function applyKeys($keys)
	{
		$ret = [];
		$key_count = count($keys);
		for ($i = 0; $i < $key_count; $i++) {
			$ret[$keys[$i]] = $this->array[$i];
		}
		return self::x($ret);
	}

	public function filter($callback, $with_keys = false)
	{
		$ret = array_filter($this->array, $callback, $with_keys ? ARRAY_FILTER_USE_BOTH : null);
		return self::x($ret);
	}

	public function keyFilter($keys)
	{
		$ret = [];
		foreach ($this->array as $key => $value) {
			if (in_array($key, $keys, true)) $ret[$key] = $value;
		}
		return self::x($ret);
	}

	public function first()
	{
		return isset($this->array[0]) ? $this->array[0] : null;
	}

	public function firstOrElse($default)
	{
		return isset($this->array[0]) ? $this->array[0] : $default;
	}

	public function last()
	{
		return $this->any() ? end($this->array) : null;
	}

	public function lastOrElse($default)
	{
		return $this->any() ? end($this->array) : $default;
	}
	
	public function getOrElse($index, $default)
	{
		return isset($this->array[$index]) ? $this->array[$index] : $default;
	}

	/**
	 * @param string $index
	 * @param mixed $default
	 * @return ArrayExtension|mixed
	 */
	public function get_x($index, $default = null)
	{
		if (is_null($default)) $default = self::x([]);
		$item = $this->getOrElse($index, $default);
		if (is_array($item)) return self::x($item);
		return $item;
	}

	public function max()
	{
		return max($this->array);
	}

	/**
	 * @param array|self $keys
	 * @param mixed $default
	 * @return array|mixed
	 */
	public function traverse($keys, $default = null)
	{
		$pt = &$this->array;
		foreach ($keys as $key) {
			if (isset($pt[$key])) {
				$pt = &$pt[$key];
			} else {
				return $default;
			}
		}
		return $pt;
	}

	/**
	 * @param array|self $keys
	 * @param mixed $default
	 * @return ArrayExtension|mixed
	 */
	public function traverse_x($keys, $default = null)
	{
		$pt = $this->traverse($keys, $default);
		if (is_array($pt)) return self::x($pt);
		return $pt;
	}

	/**
	 * @param integer $number
	 * @return array
	 */
	public function take($number)
	{
		$ret = [];
		foreach ($this->array as $key => $item) {
			$ret[$key] = $item;
			if (count($ret) >= $number) break;
		}
		return self::x($ret);
	}
	
	#endregion
	
	#region convert

	/**
	 * @param string $left
	 * @param string $right
	 * @return ArrayExtension
	 */
	public function wrap($left, $right)
	{
		return $this->map(function($item) use ($left, $right) {
			return $left . $item . $right;
		});
	}

	/**
	 * @param string $glue
	 * @return string
	 */
	public function join($glue)
	{
		return implode($glue, $this->array);
	}

	/**
	 * @param string $glue
	 * @return StringExtension
	 */
	public function join_x($glue)
	{
		return StringExtension::x($this->join($glue));
	}

	/**
	 * @param callable $element_selector
	 * @param callable $key_selector
	 * @return ArrayExtension
	 */
	public function toHash($key_selector, $element_selector = null)
	{
		$new = self::zero();
		foreach ($this as $item) {
			$new[$key_selector($item)] = $element_selector ? $element_selector($item) : $item;
		}
		return $new;
	}
	
	#endregion
	
	#region search

	/**
	 * @param $item
	 * @param bool $strict
	 * @return bool
	 */
	public function contains($item, $strict = null)
	{
		return in_array($item, $this->array, $strict);
	}
	
	public function indexOf($item, $strict = null)
	{
		return array_search($item, $this->array, $strict);
	}

	public function has($index)
	{
		return isset($this->array[$index]);
	}
	
	#endregion
	
	#region manipulate

	public function push($item)
	{
		$this->array[] = $item;
		return $this;
	}

	/**
	 * @param $item
	 * @return static $this
	 */
	public function pushImmutable($item)
	{
		$ret = $this->array;
		$ret[] = $item;
		return self::x($ret);
	}

	/**
	 * @param $array
	 * @return static $this
	 */
	public function pushRange($array)
	{
		foreach ($array as $key => $item) {
			if (is_integer($key)) $this->array[] = $item;
			else $this->array[$key] = $item;
		}
		return $this;
	}

	/**
	 * @param $array
	 * @return self
	 */
	public function pushRangeImmutable($array)
	{
		$ret = array_merge($this->array, $array);
		return self::x($ret);
	}

	/**
	 * @param $item
	 * @param bool $strict
	 * @return self $this
	 */
	public function remove($item, $strict = null)
	{
		$index = array_search($item, $this->array, $strict);
		$this->removeAt($index);
		return $this;
	}

	public function removeImmutable($item, $strict = null)
	{
		return self::x($this->array)->remove($item, $strict);
	}

	public function removeAt($index)
	{
		if (false !== $index) {
			array_splice($this->array, $index, 1);
		}
		return $this;
	}
	
	public function removeAtImmutable($index)
	{
		if (false !== $index) {
			$ret = $this->array;
			array_splice($ret, $index, 1);
			return self::x($ret);
		} else {
			return $this;
		}
	}

	public function removeAfter($index)
	{
		array_splice($this->array, $index + 1);
		return $this;
	}
	
	public function removeAfterImmutable($index)
	{
		return self::x(array_splice($this->array, $index + 1));
	}
	
	public function pop()
	{
		if ($this->length() > 0) {
			$value = $this->last();
			$this->removeAt($this->length() - 1);
			return $value;
		} else {
			return null;
		}
	}
	
	public function increment($index, $value = 1)
	{
		if (isset($this->array[$index])) {
			$this->array[$index] += $value;
		} else {
			$this->array[$index] = $value;
		}
		return $this;
	}
	
	#endregion
	
	#region sort
	
	public function sort($sort_flags = null) {
		$arr = $this->array;
		sort($arr, $sort_flags);
		return self::x($arr);
	}

	public function usort($compare_func){
		$arr = $this->array;
		usort($arr, $compare_func);
		return self::x($arr);
	}

	public function ksort($sort_flags = null)
	{
		$arr = $this->array;
		ksort($arr, $sort_flags);
		return self::x($arr);
	}

	public function reverse()
	{
		return self::x(array_reverse($this->array));
	}
	
	#endregion
	
	#region other

	/**
	 * @param int $sort_flags
	 * @return self
	 */
	public function distinct($sort_flags = null)
	{
		return self::x(array_unique($this->array, $sort_flags));
	}

	/**
	 * 連想配列を key:value オブジェクトのリストにする
	 * @param string $keyName
	 * @param string $valueName
	 * @return array
	 */
	public function toKeyValueList($keyName = 'key', $valueName = 'value')
	{
		$array = [];
		foreach ($this->array as $key => $value) {
			$array[] = [
				$keyName => $key,
				$valueName => $value,
			];
		}
		return $array;
	}

	/**
	 * @param string $to_encoding
	 * @param string $from_encoding
	 * @return self $this
	 */
	public function convertEncoding($to_encoding, $from_encoding)
	{
		mb_convert_variables($to_encoding, $from_encoding, $this->array);
		return $this;
	}
	
	#endregion

} 