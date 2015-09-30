<?php

namespace Phalcon\Evolve\View\EventListenerPlugin;

/**
 * Class EncodingPlugin
 * レンダリングされたレスポンスコンテンツを
 * クライアントに応じてエンコード変換するプラグイン
 * 
 * @package Phalcon\Evolve\View\EventListenerPlugin
 */
class EncodingPlugin implements IEventListenerPlugin {
	
	/** @var string */
	protected $internal_encoding;
	
	/** @var string */
	protected $client_encoding;
	
	public function __construct($client_encoding, $internal_encoding = 'UTF-8')
	{
		$this->client_encoding = $client_encoding;
		$this->internal_encoding = $internal_encoding;
	}
	
	public function onEvent($event, $view)
	{
		$view->setContent($this->optimizeEncoding($view->getContent()));
	}

	/**
	 * クライアントに対する文字エンコーディングのミスマッチを是正する
	 * httpd.conf の AddDefaultCharset が Off になっているものとする
	 *
	 * @param string $content
	 * @return string
	 */
	public function optimizeEncoding($content)
	{
		if ($this->client_encoding == $this->internal_encoding) return $content;
		// xml宣言 encoding="?" があれば置換する
		$pattern = '/<\?xml version="[0-9.]+" encoding="([a-zA-Z0-9\-]+)".*\?>/';
		$matches = [];
		if (preg_match($pattern, $content, $matches) > 0) {
			$target = $matches[0];
			$from_encoding = $matches[1];
			$replace_to = str_replace($from_encoding, $this->client_encoding, $target);
			$content = str_replace($target, $replace_to, $content);
		}
		// <meta charset="?"/> がなければ挿入, あれば置換する
		$pattern = '/<meta charset="[a-zA-Z0-9\-]+"\/?>/';
		$meta_charset = '<meta charset="' . $this->client_encoding . '"/>';
		if (preg_match($pattern, $content) > 0) {
			// <meta charset="?"/> がある場合は置換
			$content = preg_replace($pattern, $meta_charset, $content);
		}
		$content = mb_convert_encoding($content, $this->client_encoding, $this->internal_encoding);
		return $content;
	}

} 