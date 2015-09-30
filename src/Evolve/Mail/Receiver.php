<?php

namespace Phalcon\Evolve\Mail;

require_once 'PEAR.php';
require_once 'Mail/mimeDecode.php';

use Phalcon\Evolve\PrimitiveExtension\ArrayExtension as Ax;

class Receiver {

	const INTERNAL_ENCODING = 'UTF-8';
	const MAIL_ENCODING = 'ISO-2022-JP';

	/** @var string 入力データ */
	protected $input = "";
	/** @var string[] */
	protected $recipients = array();
	/** @var string[] */
	protected $headers = array();
	/** @var string */
	protected $body;
	/** @var string */
	protected $text;
	/** @var string */
	protected $html;
	/** @type MailBody[] */
	protected $multi_bodies;
	/** @type MailAttachment[] */
	protected $attachments = [];

	protected $structure;

	/**
	 * @param resource|string $input
	 */
	public function __construct($input = null)
	{
		if (is_string($input)) {
			$this->input = $input;
		} else {
			if (is_null($input)) { $input = fopen("php://stdin",'r'); }
			if ($input){
				while( !feof($input) ){
					$this->input .= fgets($input,4096);
				}
				fclose($input);
			}
		}
		$decoder = new \Mail_mimeDecode($this->input);
		list($recipients, $headers, $body) = $decoder->getSendArray();
		$decode = function($value) {
			mb_internal_encoding(self::MAIL_ENCODING);
			$value = mb_decode_mimeheader($value);
			mb_internal_encoding(self::INTERNAL_ENCODING);
			$value = mb_convert_encoding($value, self::INTERNAL_ENCODING, self::MAIL_ENCODING);
			return $value;
		};
		$this->recipients = is_array($recipients) ? array_map($decode, $recipients) : $decode($recipients);
		$this->headers = array_map($decode, $headers);
		$this->body = mb_convert_encoding($body, self::INTERNAL_ENCODING, self::MAIL_ENCODING);
		
		$this->structure = $decoder->decode(array(
			'include_bodies' => true,
			'decode_bodies' => true,
			'decode_headers' => true,
		));
		$this->decodeMultiPart($this->structure);
	}

	private function decodeMultiPart($decoder)
	{
		if (!empty($decoder->parts)) {
			foreach ($decoder->parts as $part) {
				$this->decodeMultiPart($part);
			}
		} elseif (!empty($decoder->body)) {
			$t1 = strtolower($decoder->ctype_primary);
			$t2 = strtolower($decoder->ctype_secondary);
			$charset = isset($decoder->ctype_parameters['charset']) ? $decoder->ctype_parameters['charset'] : 'utf-8';
			$type = "$t1/$t2";
			switch ($type) {
				case 'text/plain':
					$this->text = mb_convert_encoding($decoder->body, self::INTERNAL_ENCODING, $charset);
					$this->multi_bodies[] = new MailBody($type, $this->text);
					break;
				case 'text/html':
					$this->html = mb_convert_encoding($decoder->body, self::INTERNAL_ENCODING, $charset);
					$this->multi_bodies[] = new MailBody($type, $this->html);
					break;
				default:
					$this->attachments[] = new MailAttachment($type, $decoder->ctype_parameters['name'], $decoder->body);
					break;
			}
		}
	}

	/**
	 * @return array|\string[]
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

	/**
	 * @return string
	 */
	public function getHeadersString()
	{
		$ret = "";
		if (Ax::x($this->headers)->any()) foreach ($this->headers as $key => $value) {
			$ret .= "$key: $value\n";
		}
		return $ret;
	}

	/**
	 * @return array|\string[]
	 */
	public function getRecipients()
	{
		if (is_string($this->recipients)) {
			return [$this->recipients];
		}
		return $this->recipients;
	}

	/**
	 * @return string
	 */
	public function getSubject()
	{
		return $this->headers['Subject'];
	}

	/**
	 * @return string
	 */
	public function getOriginalCharset()
	{
		if (!isset($this->headers['Content-Type'])) return null;
		$content_type = $this->headers['Content-Type'];
		$matches = [];
		preg_match('/charset="(.*)"/', $content_type, $matches);
		return isset($matches[1]) ? $matches[1] : null;
	}

	/**
	 * @return string
	 */
	public function getFrom()
	{
		return $this->headers['From'];
	}

	/**
	 * 署名を除去して送信元メールアドレスを取得
	 * @return string
	 */
	public function getFromWithoutSign()
	{
		$from = $this->headers['From'];
		$matches = [];
		if (preg_match("/<(.+)>/", $from, $matches) > 0) {
			return $matches[1];
		}
		return $from;
	}

	/**
	 * 送信元署名を取得
	 * @return string|null
	 */
	public function getFromSign()
	{
		$from = $this->headers['From'];
		$matches = [];
		if (preg_match("/$(.+)<.+>/", $from, $matches) > 0) {
			return $matches[1];
		}
		return null;
	}

	/**
	 * @return string
	 */
	public function getDate()
	{
		return $this->headers['Date'];
	}

	/**
	 * @return string
	 */
	public function getRawBody()
	{
		return $this->body;
	}

	/**
	 * @return string
	 */
	public function getBody()
	{
		if (isset($this->text)) return $this->text;
		return $this->html;
	}

	/**
	 * プレーンテキストの本文を優先して取得する
	 * なければ HTML のタグ除去済みテキストを取得
	 * @return string
	 */
	public function getTextBody()
	{
		if (isset($this->text)) return $this->text;
		return strip_tags($this->html);
	}

	/**
	 * HTML の本文を優先して取得する
	 * なければプレーンテキストの本文を取得
	 * @return string
	 */
	public function getHtmlBody()
	{
		if (isset($this->html)) return $this->html;
		return $this->text;
	}

	public function getMultiBodies()
	{
		return $this->multi_bodies;
	}

	public function hasAttachments()
	{
		return !empty($this->attachments);
	}

	public function getAttachments()
	{
		return $this->attachments;
	}

	public function getStructure()
	{
		return $this->structure;
	}

	/**
	 * エラーメールに付帯する送信時ヘッダ情報を取得
	 *
	 * @param $name
	 * @return string
	 */
	public function getReturnMailHeader($name)
	{
		$matches = '';
		if (preg_match('/'.$name.':\s(.+)/', $this->body, $matches) === 1) {
			return trim($matches[1]);
		}
		return null;
	}
}

class MailBody {
	public $type;
	public $body;

	public function __construct($type, $body)
	{
		$this->type = $type;
		$this->body = $body;
	}
}

class MailAttachment {
	public $mime_type;
	public $file_name;
	public $binary;

	public function __construct($mime_type, $file_name, $binary)
	{
		$this->mime_type = $mime_type;
		$this->file_name = $file_name;
		$this->binary = $binary;
	}
}
