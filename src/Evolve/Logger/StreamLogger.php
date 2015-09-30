<?php

namespace Phalcon\Evolve\Logger;

use Phalcon\Evolve\Logger\Formatter\LineContextual;
use Phalcon\Logger\AdapterInterface;
use Phalcon\Logger\Adapter;
use Phalcon\Logger\Formatter\Line;

/**
 * ファイルディスクリプタを使うロガー
 * @package app\components\Logger
 */
class StreamLogger extends Adapter implements AdapterInterface {
	
	/** @type resource */
	protected $fp;
	/** @type Line */
	protected $formatter;
	/** @type integer */
	protected $log_count = 0;
	
	public function __construct($fp, $format = "%date% [%type%] %message%")
	{
		$this->fp = $fp;
		$this->formatter = new LineContextual($format);
	}

	public function getFormatter()
	{
		return $this->formatter;
	}

	public function close()
	{
		return true;
	}

	protected function logInternal($message, $type, $time, $context) {
		if (!isset($context)) $context = [];
		$context['count'] = ++$this->log_count;
		$log    = $this->getFormatter()->format($message, $type, $time, $context);
		fwrite($this->fp, $log);
	}

} 