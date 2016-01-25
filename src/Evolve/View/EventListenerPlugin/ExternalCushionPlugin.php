<?php

namespace Phalcon\Evolve\View\EventListenerPlugin;

use Phalcon\Evolve\CustomGinq as Ginq;
use Phalcon\Evolve\PrimitiveExtension\StringExtension as Sx;

class ExternalCushionPlugin implements IEventListenerPlugin {
	
	/** @type string */
	protected $local_domain;
	/** @type string */
	protected $cushion_url;
	/** @type boolean バイパスモード */
	protected $bypass = false;

	/**
	 * @param string $local_domain
	 * @param string $cushion_url
	 */
	public function __construct($local_domain, $cushion_url = null, $bypass = false) {
		$this->local_domain = $local_domain;
		$this->cushion_url = $cushion_url;
		$this->bypass = $bypass;
	}
	
	public function onEvent($event, $view)
	{
		$content = $view->getContent();
		if ($this->cushion_url) $content = $this->convertExternalLinkToCushionLink($content);
		$view->setContent($content);
	}

	public function convertExternalLinkToCushionLink($content)
	{
		// 正規表現でリンクURLを抽出 href="([^"]*)"
		if (!$this->bypass) {
			$matches = [];
			preg_match_all('/href="([^"]*)"/', $content, $matches);
			if (!isset($matches[1]) or count($matches[1]) == 0) {
				return $content;
			}
			// 外部リンクにフィルタリングし重複を排除
			/** @var string[] $urls */
			$urls = Ginq::from($matches[1])
				->where(function($url) {
					/** @var string $url */
					/** @var string[] $parts */
					$parts = parse_url($url);
					if (!isset($parts['host'])) {
						return false;
					}
					if (Sx::x($url)->endsWith('__external_direct__')) {
						return false;
					}
					return !Sx::x($parts['host'])->endsWith($this->local_domain);
				})
				->distinct()
				->toArray();
			// 対象URLをクッションページURLに置き換え href="<cushion_url>?url=<url>"
			foreach ($urls as $url) {
				$replace_to
					= $this->cushion_url . '?url=' . urlencode($url);
				$content = str_replace(
					'href="' . $url . '"',
					'href="' . $replace_to . '"',
					$content
				);
			}
		}
		return str_replace('__external_direct__', '', $content);
	}

	public function convertExternalUrlToCushionUrl($content)
	{
		$pattern = '/(href="|\]\()?https?:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:@&=+$,%#]+/';
		return preg_replace_callback($pattern, function($matches) {
			$url = Sx::x($matches[0]);
			$parts = parse_url($url);
			if (!isset($parts['host'])) {
				return $url;
			}
			if ($url->endsWith('__external_redirect__')) {
				return $url->slice(0, $url->length() - 21);
			}
			if (Sx::x($parts['host'])->endsWith($this->local_domain)) {
				return $url;
			}
			if ($this->bypass) {
				return $url;
			}
			return $this->cushion_url . '?url=' . urlencode($url);
		}, $content);
	}

} 