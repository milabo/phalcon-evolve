<?php

namespace Phalcon\Evolve\Logger\Formatter;

use Phalcon\Logger\Formatter\Line;

class LineContextual extends Line {
	
	/** @type string */
	protected $_format;

	public function __construct($format)
	{
		$this->_format = $format;
	}

	public function format($message, $type, $timestamp, $context)
	{
		$log = parent::format($message, $type, $timestamp, $context);
		foreach ($context as $key => $value) {
			$log = str_replace("%{$key}%", $value, $log);
		}
		return $log;
	}

} 