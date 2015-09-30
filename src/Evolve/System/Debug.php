<?php

namespace Phalcon\Evolve\System;

use Phalcon\Evolve\Logger\NullLogger;
use Phalcon\Logger\AdapterInterface as Logger;
use Phalcon\Evolve\PrimitiveExtension\StringExtension as Sx;
use Phalcon\Evolve\PrimitiveExtension\ArrayExtension as Ax;

/**
 * Class Debug
 * @package Phalcon\Evolve\System
 */
class Debug {
	
	const STYLE_TEXT = 'text';
	const STYLE_HTML = 'html';
	
	/** @type Logger */
	protected $logger;
	/** @type string */
	protected $style = self::STYLE_TEXT;
	/** @type bool */
	protected $enabled = false;

	public function __construct()
	{
		$this->logger = new NullLogger();
	}
	
	/**
	 * @return Logger
	 */
	public function getLogger()
	{
		return $this->logger;
	}
	
	/**
	 * @param Logger
	 * @return Debug $this
	 */
	public function setLogger($logger)
	{
		$this->logger = $logger;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getStyle()
	{
		return $this->style;
	}
	
	/**
	 * @param string
	 * @return Debug $this
	 */
	public function setStyle($style)
	{
		$this->style = $style;
		return $this;
	}
	
	/**
	 * @return bool
	 */
	public function getEnabled()
	{
		return $this->enabled;
	}
	
	/**
	 * @param bool
	 * @return self $this
	 */
	public function setEnabled($enabled)
	{
		$this->enabled = $enabled;
		return $this;
	}

	public function line($message, $method, $line, $echo = false)
	{
		if (!$this->enabled) return null;
		$str = "";
		switch ($this->style) {
			case self::STYLE_TEXT:
				$str = $this->formatText($message, $method, $line);
				break;
			case self::STYLE_HTML:
				$str = $this->formatHtml($message, $method, $line);
				break;
		}
		if ($this->logger) $this->logger->debug($str);
		if ($echo) echo $str . "\n";
		return $str;
	}
	
	public function dump($var, $method, $line, $echo = false)
	{
		if (!$echo and (!$this->enabled or !$this->logger)) return "";
		$str = "";
		switch ($this->style) {
			case self::STYLE_TEXT:
				$str = $this->dumpText($var, $method, $line);
				break;
			case self::STYLE_HTML:
				$str = $this->dumpHtml($var, $method, $line);
				break;
		}
		if ($this->logger and $this->enabled) $this->logger->debug($str);
		if ($echo) echo $str;
		return $str;
	}

	/**
	 * @param string $message
	 * @param string $method
	 * @param string $line
	 * @return string
	 */
	protected function formatText($message, $method, $line)
	{
		$method = Sx::x($method)->baseClassName();
		return $message . " on $method#$line";
	}

	/**
	 * @param string $message
	 * @param string $method
	 * @param string $line
	 * @return string
	 */
	protected function formatHtml($message, $method, $line)
	{
		$method = Sx::x($method)->baseClassName();
		return $message . "<span class=\"log-tail\"> on $method#$line</span>";
	}

	/**
	 * @param mixed $var
	 * @param string $method
	 * @param string $line
	 * @return string
	 */
	protected function dumpText($var, $method, $line)
	{
		$method = Sx::x($method)->baseClassName();
		if (is_array($var)) {
			$data = Ax::x($var)->toString("  ");
		} else if (is_object($var)) {
			if (method_exists($var, '__toString')) $data = $var->__toString();
			else $data = "<" . get_class($var) . ">#" . spl_object_hash($var);
		} else {
			$data = "$var";
		}
		return "$method#$line\n" . $data;
	}

	/**
	 * @param mixed $var
	 * @param string $method
	 * @param string $line
	 * @return string
	 */
	protected function dumpHtml($var, $method, $line)
	{
		$method = Sx::x($method)->baseClassName();
		if (is_array($var)) {
			$data = Ax::x($var)->toString("  ");
		} else if (is_object($var)) {
			if (method_exists($var, '__toString')) $data = $var->__toString();
			else $data = "<" . get_class($var) . ">#" . spl_object_hash($var);
		} else {
			$data = "$var";
		}
		$str = "<span class=\"log-tail\">$method#$line</span>\n" . $data;
		return str_replace(["\r\n", "\r", "\n"], "<br>", $str);
	}

} 