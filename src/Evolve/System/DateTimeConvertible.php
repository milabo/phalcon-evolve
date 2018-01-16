<?php

namespace Phalcon\Evolve\System;

use Phalcon\Evolve\PrimitiveExtension\StringExtension as Sx;

/**
 * DateTime timestamp 文字列 をそれぞれ変換するユーティリティトレイト
 * @package Phalcon\Evolve\System
 */
trait DateTimeConvertible {

	/**
	 * タイムスタンプを DateTime 型に変換する
	 * format が指定された場合は書式に従い文字列に変換する。
	 *
	 * @param integer $timestamp タイムスタンプ
	 * @param string $format タイムスタンプを文字列化する為のフォーマット
	 * @param string $nullValue フォーマットを指定した場合かつタイムスタンプが null である場合に使用される、代替文字列
	 * @return \DateTime|null|string
	 */
	protected static function timestampToDatetime($timestamp, $format = null, $nullValue = '-')
	{
		if (is_null($timestamp)) {
			if ($format) return $nullValue;
			else return null;
		}
		if ($format) return date($format, $timestamp);
		else return (new \DateTime())->setTimestamp($timestamp);
	}

	/**
	 * 文字列から DateTime を生成する
	 * format が指定された場合は書式に従い文字列に再変換する。
	 *
	 * @param \DateTime|string|integer $source 日時文字列
	 * @param string $format タイムスタンプを文字列化する為のフォーマット
	 * @param string $nullValue フォーマットを指定した場合かつタイムスタンプが null である場合に使用される、代替文字列
	 * @return \DateTime|null|string
	 */
	protected static function anyToDatetime($source, $format = null, $nullValue = '-')
	{
		if (is_null($source)) {
			if ($format) return $nullValue;
			else return null;
		}
		if ($source instanceof \DateTime) {
			if ($format) return $source->format($format);
			return $source;
		};
		if (is_integer($source)) {
			$source = date('Y-m-d H:i:s', $source);
		}
		$date = new \DateTime($source);
		if ($format) return $date->format($format);
		else return $date;
	}

	/**
	 * DateTime や文字列をタイムスタンプに変換する
	 *
	 * @param \DateTime|string|integer $dateTime
	 * @return integer|null
	 */
	protected static function anyToTimestamp($dateTime)
	{
		if (is_null($dateTime)) return null;
		else if (is_integer($dateTime)) {
			return $dateTime;
		} else if (is_string($dateTime)) {
			if (trim($dateTime) != "") {
				if (is_numeric($dateTime)) {
					return intval($dateTime);
				} else {
					$ts = strtotime($dateTime);
					if ($ts) return $ts;
				}
			}
			return null;
		} else return $dateTime->getTimestamp();
	}

	/**
	 * DateTime を永続用の書式の文字列に変換する
	 * 日付文字列およびタイムスタンプの指定も可
	 *
	 * @param \DateTime|string|integer $date
	 * @param string $format
	 * @return string|null
	 */
	protected static function formatDateForSave($date, $format = 'Y-m-d')
	{
		if (is_null($date)) return null;
		else if (is_integer($date)) {
			return date($format, $date);
		} else if (is_string($date)) {
			if ($ts = strtotime($date)) return date($format, $ts);
			else return null;
		} else if ($date instanceof \DateTIme) {
			return $date->format($format);
		} else {
			return $date;
		}
	}

	/**
	 * 日付範囲のジェネレータを返す
	 * @param $date_from
	 * @param $date_to
	 * @return \Generator
	 */
	protected static function dateRange($date_from, $date_to)
	{
		$date_from = self::formatDateForSave($date_from);
		$date_to = self::formatDateForSave($date_to);
		if ($date_to < $date_from) $date_to = $date_from;
		for ($ts = strtotime($date_from); date('Y-m-d', $ts) <= $date_to; $ts += 86400) {
			yield date('Y-m-d', $ts);
		}
	}

	/**
	 * 年月範囲のジェネレータを返す
	 * @param $date_from
	 * @param $date_to
	 * @return \Generator
	 */
	protected static function yearMonthRange($date_from, $date_to)
	{
		$date_from = self::formatDateForSave($date_from);
		$date_to = self::formatDateForSave($date_to);
		if ($date_to < $date_from) $date_to = $date_from;
		for ($ts = strtotime(Sx::x($date_from)->slice(0, 7)); $ts <= strtotime($date_to); $ts = strtotime('+1 month', $ts)) {
			yield date('Y-m', $ts);
		}
	}

	/**
	 * みなし生年月日に変換
	 * うるう年の 2/29 は 2/28 とする
	 * @param \DateTime|string|integer $birthday
	 * @param string $format
	 * @param string $nullValue
	 * @return \DateTime|null|string
	 */
	protected static function toDeemedBirthday($birthday, $format = null, $nullValue = '-')
	{
		if (is_null($birthday)) return $format ? $nullValue : null;
		$birthday = self::anyToDatetime($birthday);
		if ($birthday->format('m-d') == '02-29') {
			$birthday = new \DateTime($birthday->format('Y') . "-02-28");
		}
		return self::anyToDatetime($birthday, $format, $nullValue);
	}

} 