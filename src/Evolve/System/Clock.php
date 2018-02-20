<?php

namespace Phalcon\Evolve\System;

/**
 * 時刻クラス
 * @package Phalcon\Evolve\System
 */
class Clock {
	
	use DateTimeConvertible;
	
	/** @type integer */
	protected $timestamp;
	/** @type integer */
	protected $timestamp_actual;

	public function __construct($timestamp = null)
	{
		$this->timestamp
			= is_null($timestamp)
			? time()
			: $timestamp;
		$this->timestamp_actual = time();
	}

	/**
	 * 現在日時をタイムスタンプで取得
	 * @return integer
	 */
	public function nowTs()
	{
		return $this->timestamp;
	}

	/**
	 * 現在日時を DateTime または文字列として取得
	 * @param string $format
	 * @return \DateTime|string
	 */
	public function now($format = null)
	{
		return $this->timestampToDatetime($this->timestamp, $format);
	}

	/**
	 * 本日の 0時0分0秒 をタイムスタンプで取得
	 * @return integer
	 */
	public function startOfTodayTs()
	{
		return strtotime(date('Y-m-d', $this->timestamp));
	}

	/**
	 * 本日の 0時0分0秒 を DateTime または文字列として取得
	 * @param string $format
	 * @return \DateTime|string
	 */
	public function startOfToday($format = null)
	{
		return $this->timestampToDatetime($this->startOfTodayTs(), $format);
	}

	/**
	 * 本日の 23時59分59秒 をタイムスタンプで取得
	 * @return integer
	 */
	public function endOfTodayTs()
	{
		return strtotime(date('Y-m-d 23:59:59', $this->timestamp));
	}

	/**
	 * 本日の 23時59分59秒 を DateTime または文字列として取得
	 * @param string $format
	 * @return \DateTime|string
	 */
	public function endOfToday($format = null)
	{
		return $this->timestampToDatetime($this->endOfTodayTs(), $format);
	}

	/**
	 * リクエスト毎に固定の時刻ではなく実際の現在時刻をタイムスタンプで取得
	 * @return integer
	 */
	public function actualNowTs()
	{
		$diff = time() - $this->timestamp_actual;
		return $this->timestamp + $diff;
	}

	/**
	 * リクエスト毎に固定の時刻ではなく実際の現在時刻を DateTime または文字列として取得
	 * @param string $format
	 * @return \DateTime|string
	 */
	public function actualNow($format = null)
	{
		return $this->timestampToDatetime($this->actualNowTs(), $format);
	}

	/**
	 * 相対指定で時刻をタイムスタンプで取得
	 * @param string $timing
	 * @return integer
	 */
	public function fromNowTs($timing)
	{
		return strtotime($timing, $this->nowTs());
	}

	/**
	 * 相対指定で時刻を取得
	 * @param string $timing
	 * @param null $format
	 * @return \DateTime|null|string
	 */
	public function fromNow($timing, $format = null)
	{
		return $this->timestampToDatetime(strtotime($timing, $this->nowTs()), $format);
	}

	/**
	 * 相対指定で時刻を取得（本日の 0時0分0秒 を基準とする）
	 * @param $timing
	 * @param null $format
	 * @return \DateTime|null|string
	 */
	public function fromStartOfToday($timing, $format = null)
	{
		return $this->timestampToDatetime(strtotime($timing, $this->startOfTodayTs()), $format);
	}

	/**
	 * 相対指定で時刻を取得（本日の 23時59分59秒 を基準とする）
	 * @param $timing
	 * @param null $format
	 * @return \DateTime|null|string
	 */
	public function fromEndOfToday($timing, $format = null)
	{
		return $this->timestampToDatetime(strtotime($timing, $this->endOfTodayTs()), $format);
	}

} 