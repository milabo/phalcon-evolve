<?php

namespace Phalcon\Evolve\View\EventListenerPlugin;

use Phalcon\Evolve\CustomGinq as Ginq;
use Phalcon\Evolve\PrimitiveExtension\StringExtension as Sx;

class UrlSessionPlugin implements IEventListenerPlugin {
	
	/** @type string */
	protected $session_key;
	/** @type string */
	protected $session_id;
	/** @type string */
	protected $local_domain;

	/**
	 * @param string $session_id
	 * @param string $local_domain
	 * @param string $session_key
	 */
	public function __construct($session_id, $local_domain, $session_key) {
		$this->session_id = $session_id;
		$this->local_domain = $local_domain;
		$this->session_key = $session_key;
	}
	
	public function onEvent($event, $view)
	{
		$content = $view->getContent();
		$content = $this->appendSessionIdToUrl($content);
		$content = $this->appendSessionIdToFormAction($content);
		$content = $this->insertSessionIdIntoForms($content);
		$view->setContent($content);
	}

	/**
	 * 内部リンクURLにセッションIDを付与する
	 *
	 * @param string $content
	 * @return string
	 */
	public function appendSessionIdToUrl($content)
	{
		$local_domain = $this->local_domain;
		$session_key = $this->session_key;
		$session_id = $this->session_id;
		// 正規表現でリンクURLを抽出 href="([^"]*)"
		$matches = [];
		preg_match_all('/href="([^"]*)"/', $content, $matches);
		if (!isset($matches[1]) or count($matches[1]) == 0) {
			return $content;
		}
		// 内部リンクにフィルタリングし重複を排除
		/** @var Sx[] $urls */
		$urls = Ginq::from($matches[1])
			->map(Sx::getConverter())
			->where(function(Sx $url) use ($local_domain, $session_key) {
				/** @var string[] $parts */
				if ($url->startsWith('#')) return false;
				$parts = parse_url($url);
				if (isset($parts['query']) and Sx::x($parts['query'])->contains($session_key)) {
					return false;
				}
				if (isset($parts['host'])) {
					return Sx::x($parts['host'])->endsWith($local_domain);
				}
				return true;
			})
			->distinct()
			->toArray();
		// 対象URLにセッションIDを付与し置き換え href="<url>?(&)PHPSESSID=<id>"
		foreach ($urls as $url) {
			$fragment = "";
			if ($url->contains('#')) {
				$tokens = explode('#', $url);
				$url = Sx::x($tokens[0]);
				$fragment = "#" . $tokens[1];
			}
			$replace_to
				= $url . ($url->contains('?') ? '&' : '?')
				. $session_key . '=' . $session_id . $fragment;
			$content = str_replace(
				'href="' . $url . '"',
				'href="' . $replace_to . '"',
				$content
			);
		}
		return $content;
	}

	/**
	 * 内部リンクURLにセッションIDを付与する
	 *
	 * @param string $content
	 * @return string
	 */
	public function appendSessionIdToFormAction($content)
	{
		$local_domain = $this->local_domain;
		$session_key = $this->session_key;
		$session_id = $this->session_id;
		// 正規表現でリンクURLを抽出 action="([^"]*)"
		$matches = [];
		preg_match_all('/action="([^"]*)"/', $content, $matches);
		if (!isset($matches[1]) or count($matches[1]) == 0) {
			return $content;
		}
		// 内部リンクにフィルタリングし重複を排除
		/** @var Sx[] $urls */
		$urls = Ginq::from($matches[1])
			->map(Sx::getConverter())
			->where(function($url) use ($local_domain, $session_key) {
				/** @var string[] $parts */
				$parts = parse_url($url);
				if (isset($parts['query']) and Sx::x($parts['query'])->contains($session_key)) {
					return false;
				}
				if (isset($parts['host'])) {
					return Sx::x($parts['host'])->endsWith($local_domain);
				} else {
					return true;
				}
			})
			->distinct()
			->toArray();
		// 対象URLにセッションIDを付与し置き換え action="<url>?(&)PHPSESSID=<id>"
		foreach ($urls as $url) {
			$replace_to
				= $url . ($url->contains('?') ? '&' : '?')
				. $session_key . '=' . $session_id;
			$content = str_replace(
				'action="' . $url . '"',
				'action="' . $replace_to . '"',
				$content
			);
		}
		return $content;
	}

	public function insertSessionIdIntoForms($content)
	{
		$session_key = $this->session_key;
		$session_id = $this->session_id;
		$from = "</form>";
		$to = "<input type=\"hidden\" name=\"$session_key\" value=\"$session_id\" /></form>";
		return str_replace($from, $to, $content);
	}

} 