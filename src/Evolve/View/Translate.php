<?php

namespace Phalcon\Evolve\View;

use Phalcon\Translate\AdapterInterface;
use Phalcon\Evolve\PrimitiveExtension\StringExtension as Sx;

/**
 * 静的翻訳を提供する
 * @package Phalcon\Evolve\View
 */
class Translate implements AdapterInterface {
	
	/** 基準言語 */
	const DEFAULT_ENCODING = 'ja';
	
	/** @var string 現在選択されている言語設定 */
	protected $current_language;
	/** @var string 翻訳ファイルが保存されているディレクトリ */
	protected $translates_directory;
	/** @var bool 既に翻訳ファイルを読み込んだか否か（遅延ロード用フラグ） */
	protected $loaded = false;
	/** @var string[] 翻訳データ */
	protected $translate;
	/** @var string[] 共通テキストパラメタ */
	protected $common_texts = [];
	/** @var \Phalcon\Dispatcher */
	protected $dispatcher;
	
	public function __construct($dir, $lang = self::DEFAULT_ENCODING)
	{
		$this->translates_directory = $dir;
		$this->current_language = $lang;
	}

	/**
	 * @param string $lang
	 * @return self $this
	 */
	public function switchLanguage($lang)
	{
		$this->current_language = $lang;
		if ($this->loaded) {
			$this->load();
		}
		return $this;
	}
	
	public function getLanguage()
	{
		return $this->current_language;
	}

	/**
	 * @param string[] $texts
	 * @return self
	 */
	public function setCommonTexts($texts)
	{
		$this->common_texts = $texts;
		return $this;
	}

	/**
	 * @param \Phalcon\Dispatcher $dispatcher
	 * @return self
	 */
	public function setDispatcher($dispatcher)
	{
		$this->dispatcher = $dispatcher;
		return $this;
	}
	
	public function __invoke($translateKey, $placeHolders=null)
	{
		$this->query($translateKey, $placeHolders);
	}

	/**
	 * @param string $translateKey
	 * @param null $placeholders
	 * @return string
	 */
	public function __($translateKey, $placeholders=null)
	{
		return $this->strictQuery($translateKey, $placeholders);
	}

	/**
	 * @param string $translateKey
	 * @param null $placeholders
	 * @return string
	 */
	public function _($translateKey, $placeholders=null)
	{
		return $this->query($translateKey, $placeholders);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param  string $index
	 * @param  array  $placeholders
	 * @return string
	 */
	public function query($index, $placeholders=null)
	{
		if (!$this->loaded) {
			$this->load();
		}
		$result = $index = $this->getKeyPrefix() . $index;
		if ($this->exists($index)) {
			$result = $this->translate[$index];
			if (is_array($placeholders)) {
				$placeholders = array_merge($this->common_texts, $placeholders);
			} else {
				$placeholders = $this->common_texts;
			}
			foreach ($placeholders as $key => $value) {
				$result = str_replace("%$key%", $value, $result);
			}
		}
		return $this->processDictionaryPlaceholders($result);
	}

	/**
	 * @param string $index
	 * @param array $placeholders
	 * @return string
	 * @throws \ErrorException
	 */
	public function strictQuery($index, $placeholders=null)
	{
		if (!$this->loaded) {
			$this->load();
		}
		if ($this->current_language == self::DEFAULT_ENCODING) {
			return $this->query($index, $placeholders);
		}
		if (!$this->exists($index)) {
			throw new \ErrorException("Translation text not found \"$index\"");
		}
		$result = $this->translate[$this->getKeyPrefix() . $index];
		if (is_array($placeholders)) {
			$placeholders = array_merge($this->common_texts, $placeholders);
		} else {
			$placeholders = $this->common_texts;
		}
		foreach ($placeholders as $key => $value) {
			$result = str_replace("%$key%", $value, $result);
		}
		return $this->processDictionaryPlaceholders($result);
	}
	
	#region short hand and utility

	/**
	 * 辞書を引く
	 * @param $index
	 * @param bool $capitalize 先頭文字を大文字にするか否か
	 * @return string
	 */
	public function dic($index, $capitalize = false)
	{
		if (!$this->loaded) {
			$this->load();
		}
		$index = "dic.$index";
		$result = $index;
		if ($this->exists($index)) {
			$result = $this->translate[$index];
		}
		$result = $this->processDictionaryPlaceholders($result);
		if ($capitalize) {
			$result[0] = strtoupper($result[0]);
		}
		return $result;
	}

	/**
	 * テキストに含まれる辞書参照プレースホルダを処理
	 * @param $text
	 * @return string 
	 */
	private function processDictionaryPlaceholders($text)
	{
		$matches = [];
		preg_match_all('/%dic\.([a-zA-Z0-9_\-]+)%/', $text, $matches);
		foreach ($matches[1] as $key) {
			if ($value = $this->dic($key)) {
				$text = str_replace("%dic.{$key}%", $value, $text);
			}
		}
		return $text;
	}

	public function minLength($length)
	{
		return $this->query('gen.min_length', ['value' => $length]);
	}

	public function maxLength($length)
	{
		return $this->query('gen.max_length', ['value' => $length]);
	}

	public function minValue($value)
	{
		return $this->query('gen.min_value', ['value' => $value]);
	}

	public function maxValue($value)
	{
		return $this->query('gen.max_value', ['value' => $value]);
	}
	
	#endregion
	
	public function exists($index)
	{
		if (!$this->loaded) {
			$this->load();
		}
		return isset($this->translate[$index]);
	}
	
	protected function load()
	{
		$path = $this->translates_directory . '/' . $this->current_language . '.csv';
		$path = preg_replace('/\/{2,}/', '/', $path);
		if (file_exists($path) and $fp = @fopen($path, 'r')) {
			while (false !== ($data = fgetcsv($fp))) {
				if (substr($data[0], 0, 1) === '#' || !isset($data[1])) {
					continue;
				}
				$this->translate[$data[0]] = $data[1];
			}
			@fclose($fp);
		}
		$this->loaded = true;
	}
	
	protected function getKeyPrefix()
	{
		if ($this->dispatcher) {
			$controller = Sx::x($this->dispatcher->getHandlerClass())
				->replace('Controller')
				->classNameToSnake()
				->unwrap();
			$action = $this->dispatcher->getActionName();
			return "$controller.$action.";
		} else {
			return "";
		}
	}

	public function dump()
	{
		if (!$this->loaded) $this->load();
		echo "translate dump\n";
		var_dump($this->translate);
	}

	/**
	 * Returns the translation string of the given key
	 *
	 * @param    string $translateKey
	 * @param    array $placeholders
	 * @return    string
	 * @param mixed $translateKey
	 * @param mixed $placeholders
	 */
	public function t($translateKey, $placeholders = null)
	{
		return $this->query($translateKey, $placeholders);
	}
} 