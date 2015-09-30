<?php

namespace Phalcon\Evolve\System;

use Phalcon\DI\Injectable;

/**
 * ファイルをRedis上に一時保管するためのロッカークラス
 * @package Phalcon\Evolve\System
 * @property \Phalcon\Config $config
 */
class FileLocker extends Injectable
{

	protected $stash_ttl = 1200;

	public function setStashTtl($ttl)
	{
		$this->stash_ttl = $ttl;
	}

	/**
	 * @param bool $shared
	 * @return \Redis
	 */
	protected function getRedis($shared = false)
	{
		if ($shared) $this->di->getShared('redis');
		return $this->di->get('redis');
	}

	/**
	 * ファイルを Redis 上に収納してトークンを返却する
	 * @param string $csv_path
	 * @param string $convert_encoding_from
	 * @return string
	 */
	public function stash($csv_path, $convert_encoding_from = null)
	{
		$content = file_get_contents($csv_path);
		if ($convert_encoding_from) {
			$content = mb_convert_encoding($content, 'UTF-8', $convert_encoding_from);
		}
		$content = gzcompress($content);
		$token = hash('sha256', $content);
		$this->getRedis(true)->setex("file-stash:$token", $this->stash_ttl, $content);
		return $token;
	}

	/**
	 * 格納されたデータをファイルに出力してファイルパスを返却する
	 * @param string $token
	 * @return string
	 */
	public function retrieve($token)
	{
		$content = $this->getRedis(true)->get("file-stash:$token");
		if (!$content) return null;
		$csv_path = $this->config['application']['tempDir'] . $token;
		$content = gzuncompress($content);
		file_put_contents($csv_path, $content);
		return $csv_path;
	}

	/**
	 * 格納されたデータを削除する
	 * @param string $token
	 */
	public function erase($token)
	{
		$this->getRedis(true)->delete("file-stash:$token");
	}

}