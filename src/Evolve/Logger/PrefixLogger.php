<?php

namespace Phalcon\Evolve\Logger;

use Phalcon\Logger\Adapter;

/**
 * ロガーをラップしてプレフィクスを追加するだけ
 * @package app\components\Logger
 */
class PrefixLogger {
	
	/** @type Adapter */
	protected $logger;
	/** @type string */
	protected $prefix;
	
	public function __construct($logger, $prefix = "")
	{
		$this->logger = $logger;
		$this->prefix = $prefix;
	}

	public function getLogger()
	{
		return $this->logger;
	}

	public function setFormatter($formatter)
	{
		$this->logger->setFormatter($formatter);
	}

	public function getFormatter()
	{
		return $this->logger->getFormatter();
	}

	/**
	 * Sends a message to each registered logger
	 *
	 * @param string $message
	 * @param int $type
	 * @param array $context
	 */
	public function log($type, $message, $context = null)
	{
		$this->logger->log($type, $this->prefix . $message, $context);
	}
	
	/**
	 * Sends/Writes an emergency message to the log
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function emergency($message, $context = null){
		$this->logger->emergency($this->prefix . $message, $context);
	}


	public function emergence($message, $context = null){
		$this->logger->emergence($this->prefix . $message, $context);
	}


	/**
	 * Sends/Writes a debug message to the log
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function debug($message, $context = null){
		$this->logger->debug($this->prefix . $message, $context);
	}


	/**
	 * Sends/Writes an error message to the log
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function error($message, $context = null){
		$this->logger->error($this->prefix . $message, $context);
	}


	/**
	 * Sends/Writes an info message to the log
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function info($message, $context = null){
		$this->logger->info($this->prefix . $message, $context);
	}


	/**
	 * Sends/Writes a notice message to the log
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function notice($message, $context = null){
		$this->logger->notice($this->prefix . $message, $context);
	}


	/**
	 * Sends/Writes a warning message to the log
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function warning($message, $context = null){
		$this->logger->warning($this->prefix . $message, $context);
	}


	/**
	 * Sends/Writes an alert message to the log
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function alert($message, $context = null){
		$this->logger->alert($this->prefix . $message, $context);
	}

} 