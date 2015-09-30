<?php

namespace Phalcon\Evolve\View\EventListenerPlugin;

use Phalcon\Evolve\CustomGinq as Ginq;
use Phalcon\Mvc\UrlInterface;
use Phalcon\Evolve\PrimitiveExtension\ArrayExtension as Ax;
use Phalcon\Evolve\PrimitiveExtension\StringExtension as Sx;

class BaseUrlFixPlugin implements IEventListenerPlugin {
	
	/** @type UrlInterface */
	protected $url;
	/** @type array|string[] */
	protected $exclusives;
	
	public function __construct($url, $exclusives)
	{
		$this->url = $url;
		$this->exclusives = $exclusives;
	}
	
	public function onEvent($event, $view)
	{
		$content = $view->getContent();
		if (($base_uri = $this->url->getBaseUri()) !== '/') {
			$content = $this->fixBaseUrl($content, $base_uri);
		}
		$view->setContent($content);
	}

	public function fixBaseUrl($content, $base_uri)
	{
		// 正規表現でリンクURLを抽出 href="([^"]*)"
		$matches = [];
		preg_match_all('/href="([^"]*)"/', $content, $matches);
		if (!isset($matches[1]) or count($matches[1]) == 0) {
			return $content;
		}
		// 内部リンクにフィルタリングし重複を排除
		/** @var string[] $urls */
		$urls = Ginq::from($matches[1])
			->where(function($url) use ($base_uri) {
				$parts = Ax::x(parse_url($url));
				// ホスト部なし & 絶対パス & ベースパス不一致 なら対象
				if ($parts->has('host') or !$parts->has('path')) return false;
				$path = Sx::x($parts['path']);
				return $path->startsWith('/')
					&& !$path->startsWith($base_uri)
					&& !$path->startsWith($this->exclusives);
			})
			->distinct()
			->toArray();
		// 対象URLのベースパス追加
		foreach ($urls as $url) {
			$replace_to
				= str_replace('//', '/', $base_uri . $url);
			$content = str_replace(
				'href="' . $url . '"',
				'href="' . $replace_to . '"',
				$content
			);
		}
		return $content;
	}

} 