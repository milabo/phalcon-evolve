<?php

namespace Phalcon\Evolve\Mail;

use Phalcon\Evolve\Logger\NullLogger;
use Phalcon\Config;

require_once 'PEAR.php';
require_once 'Net/SMTP.php';
require_once 'Mail/mime.php';

/**
 * Class Sender
 * Volt 対応メールセンダークラス
 *
 * @package Phalcon\Evolve\Mail
 */
class Sender implements SenderInterface {

	const INTERNAL_ENCODING = 'UTF-8';
	const MAIL_ENCODING = 'ISO-2022-JP';

	/** @var \Net_SMTP */
	private $smtp = null;

	/** @var string */
	private $host;

	/** @var string */
	private $user = null;

	/** @var string */
	private $password = null;

	/** @var bool */
	private $is_connected = false;

	/** @var array|string */
	private $sender_address;

	/** @var string */
	private $subject;

	/** @var array|string */
	private $receiver_addresses;

	/** @var array|string */
	private $cc_addresses;

	/** @var array|string */
	private $bcc_addresses;

	/** @var string 返信先アドレス */
	private $reply_to;

	/** @var string エラー返送先アドレス */
	private $return_path;

	/** @var \Phalcon\Mvc\View\Engine\Volt */
	private $volt;

	/** @var string */
	private $template_dir;

	/** @var \Phalcon\Logger\AdapterInterface */
	private $logger;

	/** @var string[]|array */
	private $additional_headers = array();

	/** @var string[]|array */
	private $last_headers;

	/** @var string */
	private	$last_body;

	/**
	 * @param array $settings
	 * host: [必須]SMTPサーバのIPアドレス
	 * port: [必須]SMTPサーバのポート番号
	 * headers: デフォルトで出力するヘッダ配列
	 * sender_address: 送信元メールアドレス
	 * return_path: 配送エラーメール送信先メールアドレス
	 * mail_encoding: メールエンコーディング
	 */
	public function __construct($settings)
	{
		if (!is_array($settings)) $settings = (array)$settings;
		$this->host = $settings["host"];
		$this->user = isset($settings['user']) ? $settings['user'] : null;
		$this->password = isset($settings['password']) ? $settings['password'] : null;
		$this->smtp = new \Net_SMTP($this->host);
		if (array_key_exists('sender_address', $settings))
			$this->sender_address = self::unwrapObject($settings['sender_address']);
		if (array_key_exists('return_path', $settings))
			$this->return_path = $settings['return_path'];
		if (array_key_exists('headers', $settings))
			$this->additional_headers = (array)$settings['headers'];
		$this->setLogger(new NullLogger());
	}

	/**
	 * SMTP サーバの IP アドレスを取得する
	 */
	public function getHost()
	{
		return $this->host;
	}

	public function getSmtp()
	{
		return $this->smtp;
	}

	/**
	 * SMTP サーバに接続する
	 * @param string $user
	 * @param string $password
	 * @return bool
	 * @throws \ErrorException
	 */
	public function connect($user = null, $password = null)
	{
		$ret = $this->smtp->connect();
		if (true !== $ret) {
			throw new \ErrorException($ret->getMessage(), -1);
		}
		$user = $user ?: $this->user;
		$password = $password ?: $this->password;
		if ($user and $password) {
			$ret = $this->smtp->auth($user, $password, 'LOGIN');
			if (true !== $ret) {
				throw new \ErrorException($ret->getMessage(), -1);
			}
		}
		return $this->is_connected = true;
	}

	/**
	 * SMTP サーバから切断する
	 */
	public function close()
	{
		$this->smtp->disconnect();
		$this->is_connected = false;
	}

	/**
	 * @param \Phalcon\Logger\AdapterInterface $logger
	 * @return self $this
	 */
	public function setLogger($logger)
	{
		$this->logger = $logger;
		return $this;
	}

	/**
	 * 送信元メールアドレス
	 * ハッシュとして設定することで、署名を付与できます。
	 * 例: array('鈴木 健太' => 'sukobuto@gmail.com')
	 *
	 * @param string|array $sender_address
	 * @return self $this
	 */
	public function setSenderAddress($sender_address)
	{
		$this->sender_address = self::unwrapObject($sender_address);
		return $this;
	}

	/**
	 * @return array|string[]
	 */
	public function getSenderAddress()
	{
		return is_array($this->sender_address) ? $this->sender_address : [$this->sender_address];
	}

	/**
	 * @param string $subject
	 * @return self $this
	 */
	public function setSubject($subject)
	{
		$this->subject = $subject;
		return $this;
	}

	public function getSubject()
	{
		return $this->subject;
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @return self $this
	 */
	public function setHeader($name, $value)
	{
		$this->additional_headers[$name] = self::encodeHeader($value);
		return $this;
	}

	/**
	 * @param array $headers
	 * @return $this
	 */
	public function setHeaders($headers)
	{
		foreach ($headers as $name => $value) {
			$this->additional_headers[$name] = self::encodeHeader($value);
		}
		return $this;
	}

	/**
	 * @param string $name
	 * @return self $this
	 */
	public function removeHeader($name)
	{
		unset($this->additional_headers[$name]);
		return $this;
	}

	/**
	 * @return $this
	 */
	public function clearHeaders()
	{
		$this->additional_headers = array();
		return $this;
	}

	public function getLastHeaders()
	{
		return $this->last_headers;
	}

	/**
	 * @return string
	 */
	public function getLastHeadersString()
	{
		$ret = "";
		if (!empty($this->last_headers)) foreach ($this->last_headers as $key => $value) {
			$ret .= "{$key}: $value\n";
		}
		return $ret;
	}

	public function getLastBody()
	{
		return $this->last_body;
	}

	/**
	 * 送信先アドレスを設定する
	 * ハッシュとして設定することで、署名を付与できます。
	 *
	 * @param string|array $receiver_addresses
	 * @return self $this
	 */
	public function setReceiverAddresses($receiver_addresses)
	{
		$this->receiver_addresses = self::unwrapObject($receiver_addresses);
		return $this;
	}

	/**
	 * @return array|string[]
	 */
	public function getReceiverAddresses()
	{
		return is_array($this->receiver_addresses) ? $this->receiver_addresses : [$this->receiver_addresses];
	}

	/**
	 * CCアドレスを設定する
	 * ハッシュとして設定することで、署名を付与できます。
	 *
	 * @param string|array $cc_addresses
	 * @return self $this
	 */
	public function setCcAddresses($cc_addresses)
	{
		$this->cc_addresses = $cc_addresses;
		return $this;
	}

	/**
	 * BCCアドレスを設定する
	 * ハッシュとして設定することで、署名を付与できます。
	 *
	 * @param string|array $bcc_addresses
	 * @return self $this
	 */
	public function setBccAddresses($bcc_addresses)
	{
		$this->bcc_addresses = $bcc_addresses;
		return $this;
	}

	/**
	 * 返信先アドレスを設定する
	 *
	 * @param string $reply_to
	 * @return self $this
	 */
	public function setReplyTo($reply_to)
	{
		$this->reply_to = $reply_to;
		return $this;
	}

	public function getReplyTo()
	{
		return $this->reply_to;
	}

	/**
	 * 配送エラー返送先アドレスを設定する
	 *
	 * @param $return_path
	 * @return self $this
	 */
	public function setReturnPath($return_path)
	{
		$this->return_path = $return_path;
		return $this;
	}

	public function getReturnPath()
	{
		return $this->return_path;
	}

	/**
	 * テンプレートをコンパイルするための Volt を設定する
	 *
	 * @param \Phalcon\Mvc\View\Engine\Volt $voltService
	 * @return self $this
	 */
	public function setVolt($voltService)
	{
		$this->volt = $voltService;
		return $this;
	}

	/**
	 * メールテンプレートが格納されているディレクトリを設定する
	 *
	 * @param string $template_dir
	 * @return self $this
	 */
	public function setTemplateDirectory($template_dir)
	{
		$this->template_dir = $template_dir;
		return $this;
	}

	/**
	 * 本文を送信
	 * @param string $body
	 * @param null|array $attachments
	 * @return array 0:code(int), 1:message(string)
	 * @throws \Exception
	 */
	public function send($body, $attachments = null)
	{
		$this->last_body = $body;
		$recipients = array();
		$headers = $this->additional_headers;
		$headers['Subject'] = self::encodeHeader($this->subject);
		$headers['From'] = $this->sender_address;
		$from = $this->return_path ?: $this->sender_address;
		if (is_array($this->sender_address)) {
			foreach ($this->sender_address as $idx => $addr) {
				$headers['From'] = is_string($idx) ? self::encodeHeader($idx) . "<${addr}>" : $addr;
				$from = $this->return_path ?: $addr;
				break;
			}
		}
		$to_recipients = [];
		$cc_recipients = [];
		$bcc_recipients = [];
		self::procParam('To', $to_recipients, $headers, $this->receiver_addresses);
		if ($this->cc_addresses) self::procParam('Cc', $cc_recipients, $headers, $this->cc_addresses);
		if ($this->bcc_addresses) self::procParam('Bcc', $bcc_recipients, $headers, $this->bcc_addresses);
		if ($this->reply_to) $headers['Reply-To'] = $this->reply_to;
		if ($this->return_path) $headers['Return-Path'] = $this->return_path;
		$this->logger->info(
			"\n(recipients):\n"
			. var_export($recipients, true)
			. "\n\n(headers):\n"
			. var_export($headers, true)
			. "\n\n(body):\n"
			. $body);
		$body = str_replace(["\r\r\n" ,"\r\n", "\r"], "\n", $body);
		$body = mb_convert_encoding($body, self::MAIL_ENCODING, 'auto');
		if (is_array($attachments) and !empty($attachments)) {
			$mimeObject = new \Mail_Mime("\n");
			$mimeObject->setTxtBody($body);
			foreach ($attachments as $file => $type) {
				$mimeObject->addAttachment($file, $type);
			}
			$body = $mimeObject->get([
				'head_charset' => self::MAIL_ENCODING,
				'text_charset' => self::MAIL_ENCODING,
			]);
			$headers = $mimeObject->headers($headers);
		}

		// メール送信シーケンスを開始
		if (!$this->is_connected) $this->connect();
		if (true !== $this->smtp->mailFrom($from)) $this->trapError();
		foreach ($to_recipients as $to) {
			if (true !== $this->smtp->rcptTo($to)) $this->trapError();
		}
		$data = "";
		foreach ($headers as $key => $value) {
			$data .= "{$key}: $value\n";
		}
		$data .= "\n" . $body;
		if (true !== $this->smtp->data($data)) $this->trapError();
		$this->last_headers = $headers;
		$this->smtp->rset();
		return $this;
	}

	private function trapError()
	{
		list ($code, $msg) = $this->smtp->getResponse();
		$this->smtp->rset();
		throw new \ErrorException($msg, $code);
	}

	/**
	 * テンプレートを使って本文をレンダリングし送信
	 * @param string $template_name
	 * @param array $params
	 * @return self $this
	 */
	public function sendRender($template_name, $params)
	{
		$buffering = ob_start();
		$this->logger->debug('Sender->sendRender buffering : ' . $buffering);
		$this->volt->render($this->template_dir . $template_name, $params);
		$body = ob_get_clean();
		$this->logger->debug('Sender->sendRender body : ' . $body);
		return $this->send($body);
	}

	/**
	 * Process parameter of Mail - TO, CC, BCC
	 * @param string $headername 'To', 'Cc', or 'Bcc'.
	 * @param array $recipients (REF) The list of the mail recipients adresses to fill up.
	 * @param array $headers (REF) The list of the mail headers to fill up.
	 * @param mixed $param The parameter of Mail - TO, CC, BCC.
	 */
	private static function procParam($headername, array &$recipients, array &$headers, $param) {
		$headers[$headername] = "";
		if (is_array($param)) {
			$dlm = '';
			foreach ($param as $idx => $addr) {
				if (is_string($idx)) {
					$headers[$headername] .= "${dlm}" . self::encodeHeader($idx) . "<${addr}>";
				} else {
					$headers[$headername] .= "${dlm}${addr}";
				}
				$recipients[] = $addr;
				$dlm = ',';
			}
		} else {
			$headers[$headername] .= $param;
			$recipients[] = $param;
		}
	}

	/**
	 * Encode mail subject for MIME header.
	 * @param string $subject The mail subject.
	 * @return string The encoded subject.
	 */
	private static function encodeHeader($subject)
	{
		$subject = mb_convert_encoding($subject, self::MAIL_ENCODING, self::INTERNAL_ENCODING);
		mb_internal_encoding(self::MAIL_ENCODING);
		$subject = mb_encode_mimeheader($subject, self::MAIL_ENCODING);
		mb_internal_encoding(self::INTERNAL_ENCODING);
		return $subject;
	}

	/**
	 * @param $object
	 * @return mixed
	 */
	public static function unwrapObject($object)
	{
		if ($object instanceof \stdClass) return (array)$object;
		else if ($object instanceof Config) return $object->toArray();
		return $object;
	}

}