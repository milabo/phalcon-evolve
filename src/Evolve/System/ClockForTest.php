<?php

namespace Phalcon\Evolve\System;

/**
 * テスト用時刻クラス
 * @package Phalcon\Evolve\System
 */
class ClockForTest extends Clock {
	
	/** @type integer */
	protected $timestamp_original;

	public function __construct($timestamp = null)
	{
		parent::__construct($timestamp);
		$this->timestamp_original = $this->timestamp;
	}

	/**
	 * @param integer $timestamp
	 * @return self $this
	 */
	public function set($timestamp)
	{
		$this->timestamp = $timestamp;
		return $this;
	}

	public function reset()
	{
		$this->timestamp = $this->timestamp_original;
		return $this;
	}

} 