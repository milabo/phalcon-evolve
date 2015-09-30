<?php

namespace Phalcon\Evolve\View;

use Phalcon\DI\Injectable;
use Phalcon\Mvc\View\Engine\Volt;
use Phalcon\Evolve\PrimitiveExtension\StringExtension as Sx;

/**
 * Volt テンプレートエンジンを直接利用するためのユーティリティ
 * @package Phalcon\Evolve\View
 * @property \Phalcon\Logger\AdapterInterface $logger
 */
class VoltUtility extends Injectable {
	
	/** @type Volt */
	protected $volt;
	/** @type bool */
	protected static $error_handler_initialized = false;

	public function __construct($volt)
	{
		$this->volt = $volt;
	}

	/**
	 * Volt によりレンダリングしたコンテンツを文字列として返却する
	 * @param string $template_path
	 * @param array $params
	 * @param bool $report_error_in_result
	 * @return string
	 * @throws RenderingError
	 */
	public function render($template_path, $params, $report_error_in_result = false)
	{
		if ($report_error_in_result) self::initializeErrorHandler();
		try {
			ob_start();
			$this->volt->render($template_path, $params);
			return ob_get_clean();
		} catch (\Exception $ex) {
			$this->logger->error("an error occurred at processing $template_path. " . $ex->getMessage());
			ob_end_clean();
			$e = $this->makeException($ex);
			if ($report_error_in_result) {
				return "###" . $e->getMessage() . "###\n" . $ex->getTraceAsString();
			} else throw $e;
		}
	}

	private function makeException(\Exception $ex)
	{
		$orig_message = Sx::x($ex->getMessage());
		if ($orig_message->startsWith('Syntax error,')) {
			return new RenderingError("書式エラー", 0, $ex);
		}
		if ($orig_message->startsWith('Undefined variable:')) {
			$tokens = explode(':', $orig_message);
			return new RenderingError("使用不可能な変数:{$tokens[1]} ");
		}
		return new RenderingError("不明なエラー: {$orig_message}", 0 ,$ex);
	}

	private static function initializeErrorHandler()
	{
		if (self::$error_handler_initialized) return;
		self::$error_handler_initialized = true;
		register_shutdown_function(
			function(){
				$e = error_get_last();
				if( $e['type'] == E_ERROR ||
					$e['type'] == E_PARSE ||
					$e['type'] == E_CORE_ERROR ||
					$e['type'] == E_COMPILE_ERROR ||
					$e['type'] == E_USER_ERROR ){
					// お好きな処理を書く
					echo "致命的なエラーが発生しました。\n";
					echo "Error type:\t {$e['type']}\n";
					echo "Error message:\t {$e['message']}\n";
					echo "Error file:\t {$e['file']}\n";
					echo "Error line:\t {$e['line']}\n";
				}
			}
		);

		set_error_handler(function($errno, $errstr, $errfile, $errline){
			throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
		});
	}
	
}

class RenderingError extends \Exception {}