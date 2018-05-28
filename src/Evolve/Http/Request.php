<?php

namespace Phalcon\Evolve\Http;

use Phalcon\Http\Request\File;
use Phalcon\Evolve\PrimitiveExtension\ArrayExtension as Ax;

/**
 * Class Request
 * @package Phalcon\Evolve\Http
 * @property \Phalcon\Http\Request $request
 */
class Request extends \Phalcon\Http\Request {
	
	protected $internal_encoding = 'UTF-8';
	protected $convert_encoding_from;

	/** @type File[] */
	protected $uploadedFiles = array();
	
	public function setConvertEncodingFrom($convert_encoding_from)
	{
		$this->convert_encoding_from = $convert_encoding_from;
		return $this;
	}
	
	public function setInternalEncoding($internal_encoding)
	{
		$this->internal_encoding = $internal_encoding;
		return $this;
	}
	
	protected function convert($var, $defaultValue)
	{
		// 変換元文字コードが設定されていなければそのまま返却
		if (!isset($this->convert_encoding_from)) return $var;
		// 変換元文字コードと内部文字コードが同じならそのまま返却
		if ($this->convert_encoding_from == $this->internal_encoding) return $var;
		// デフォルト値が使われた場合はそのまま返却
		if ($var === $defaultValue) return $var;
		mb_convert_variables($this->internal_encoding, $this->convert_encoding_from, $var);
		return $var;
	}
	
	public function get($name=null, $filters=null, $defaultValue=null)
	{
		return $this->convert(parent::get($name, $filters, $defaultValue), $defaultValue);
	}

	public function getWithoutConvert($name, $filters = null, $defaultValue = null)
	{
		return parent::get($name, $filters, $defaultValue);
	}
	
	public function getPost($name=null, $filters=null, $defaultValue=null)
	{
		return $this->convert(parent::getPost($name, $filters, $defaultValue), $defaultValue);
	}

	public function getPut($name=null, $filters=null, $defaultValue=null)
	{
		return $this->convert(parent::getPut($name, $filters, $defaultValue), $defaultValue);
	}
	
	public function getQuery($name=null, $filters=null, $defaultValue=null)
	{
		return $this->convert(parent::getQuery($name, $filters, $defaultValue), $defaultValue);
	}

	public function getUploadedFile($key)
	{
		if (!isset($this->uploadedFiles)) {
			// アップロードされたファイルをキーでマップ化する
			foreach ($this->request->getUploadedFiles() as $file)
				$this->uploadedFiles[$file->getKey()] = $file;
		}
		return array_key_exists($key, $this->uploadedFiles)
			? $this->uploadedFiles[$key]
			: false;
	}

	/**
	 * 複数のリクエストフィールドを接続して返す
	 * @param array $fields
	 * @param string $delimiter
	 * @return string
	 */
	public function getConnectedField(array $fields, $delimiter = '-')
	{
		return Ax::x($fields)
			->map(function($field) {
				return $this->get($field, 'int');
			})->join($delimiter);
	}

	/**
	 * @param \Phalcon\Http\Request\File $file
	 * @return bool
	 * @throws \ErrorException
	 */
	public function isSafetyFile($file)
	{
		$file_extension = strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION));
		$safety_extensions = ['jpg', 'jpeg', 'png', 'gif', 'txt', 'pdf', 'csv', 'zip'];
		return in_array($file_extension, $safety_extensions);
	}

} 