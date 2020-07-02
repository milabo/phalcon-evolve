<?php


namespace Phalcon\Evolve\Mail;


interface SenderInterface
{
	/**
	 * SMTP サーバに接続する
	 * @param string $user
	 * @param string $password
	 * @return bool
	 * @throws \ErrorException
	 */
	public function connect($user = null, $password = null);

	/**
	 * SMTP サーバから切断する
	 */
	public function close();

	/**
	 * @param \Phalcon\Logger\AdapterInterface $logger
	 * @return self $this
	 */
	public function setLogger($logger);

	/**
	 * 送信元メールアドレス
	 * ハッシュとして設定することで、署名を付与できます。
	 * 例: array('鈴木 健太' => 'sukobuto@gmail.com')
	 *
	 * @param string|array $sender_address
	 * @return self $this
	 */
	public function setSenderAddress($sender_address);

	/**
	 * @param string $subject
	 * @return self $this
	 */
	public function setSubject($subject);

	public function getSubject();

	/**
	 * @param string $name
	 * @param string $value
	 * @return self $this
	 */
	public function setHeader($name, $value);

	/**
	 * @param array $headers
	 * @return $this
	 */
	public function setHeaders($headers);

	/**
	 * @param string $name
	 * @return self $this
	 */
	public function removeHeader($name);

	/**
	 * @return $this
	 */
	public function clearHeaders();

	public function getLastHeaders();

	/**
	 * @return string
	 */
	public function getLastHeadersString();

	public function getLastBody();

	/**
	 * 送信先アドレスを設定する
	 * ハッシュとして設定することで、署名を付与できます。
	 *
	 * @param string|array $receiver_addresses
	 * @return self $this
	 */
	public function setReceiverAddresses($receiver_addresses);

	/**
	 * @return array|string[]
	 */
	public function getReceiverAddresses();

	/**
	 * CCアドレスを設定する
	 * ハッシュとして設定することで、署名を付与できます。
	 *
	 * @param string|array $cc_addresses
	 * @return self $this
	 */
	public function setCcAddresses($cc_addresses);

	/**
	 * BCCアドレスを設定する
	 * ハッシュとして設定することで、署名を付与できます。
	 *
	 * @param string|array $bcc_addresses
	 * @return self $this
	 */
	public function setBccAddresses($bcc_addresses);

	/**
	 * 返信先アドレスを設定する
	 *
	 * @param string $reply_to
	 * @return self $this
	 */
	public function setReplyTo($reply_to);

	public function getReplyTo();

	/**
	 * 配送エラー返送先アドレスを設定する
	 *
	 * @param $return_path
	 * @return self $this
	 */
	public function setReturnPath($return_path);

	public function getReturnPath();

	/**
	 * テンプレートをコンパイルするための Volt を設定する
	 *
	 * @param \Phalcon\Mvc\View\Engine\Volt $voltService
	 * @return self $this
	 */
	public function setVolt($voltService);

	/**
	 * メールテンプレートが格納されているディレクトリを設定する
	 *
	 * @param string $template_dir
	 * @return self $this
	 */
	public function setTemplateDirectory($template_dir);

	/**
	 * 本文を送信
	 * @param string $body
	 * @param null|array $attachments
	 * @return array 0:code(int), 1:message(string)
	 * @throws \Exception
	 */
	public function send($body, $attachments = null);

	/**
	 * テンプレートを使って本文をレンダリングし送信
	 * @param string $template_name
	 * @param array $params
	 * @return self $this
	 */
	public function sendRender($template_name, $params);

	/**
	 * @param $object
	 * @return mixed
	 */
	public static function unwrapObject($object);

}
