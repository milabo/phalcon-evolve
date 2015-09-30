<?php

namespace Phalcon\Evolve\Http;

use Phalcon\Evolve\PrimitiveExtension\StringExtension as Sx;

/**
 * Class ClientResolver
 * クライアントの情報を解析
 * 
 * @package Phalcon\Evolve\Http
 */
class ClientPlatform {
	
	const
		DEVICE_CLASS_FEATURE = 'fp',
		DEVICE_CLASS_SMART = 'sp',
		DEVICE_CLASS_TABLET = 'tb',
		DEVICE_CLASS_PC = 'pc',
		CARRIER_NTT = 'DoCoMo',
		CARRIER_KDDI = 'EZweb',
		CARRIER_SBM = 'SoftBank',
		CARRIER_WILLCOM = 'WILLCOM',
		CARRIER_OTHER = 'other';

	/** @var string */
	protected $user_agent;

	/** @var string */
	protected $device_class;
	
	/** @var string */
	protected $carrier;
	
	/** @var string */
	protected $uid;

	/** @type array */
	protected $crawlers;

	public function __construct($user_agent = null)
	{
		$this->user_agent
			= isset($user_agent)
			? $user_agent
			: ( isset($_SERVER['HTTP_USER_AGENT'])
				? $_SERVER['HTTP_USER_AGENT']
				: '' );
	}
	
	public function __toString()
	{
		return $this->getDeviceClass() . ' ' . $this->getCarrier();
	}

	/**
	 * クッキーが有効か判定する
	 * セッションスタートアクション実行以降でのみ正しく判定できる
	 * 
	 * @param bool $suppress_exception
	 * @return bool|null
	 * @throws \ErrorException
	 */
	public function cookieEnabled($suppress_exception = false)
	{
		$session_name = session_name();
		if (isset($_COOKIE[$session_name])) {
			return true;
		}
		if (isset($_GET[$session_name])) {
			return false;
		}
		if (!$suppress_exception)
			throw new \ErrorException("Cannot detect cookie is enabled or not.");
		return null;
	}
	
	public function getUserAgent()
	{
		return $this->user_agent;
	}

	/**
	 * デバイスの分類を取得します
	 * タブレットとPCを同一視したいなどの場合は
	 * 引数 $marge_classify で次のようにマッピングを指定してください。
	 * $client->getDeviceClass(array(
	 *	ClientPlatform::DEVICE_CLASS_TABLET => ClientPlatform::DEVICE_CLASS_PC,
	 * ));
	 * 
	 * @param array $marge_classify
	 * @return string
	 */
	public function getDeviceClass($marge_classify = array())
	{
		if (!isset($this->device_class)) {
			$ua = $this->getUserAgent();

			$mobile = strpos($ua, 'Mobile') !== false;
			$android = strpos($ua, 'Android') !== false;
			$iphone = strpos($ua, 'iPhone') !== false;
			$windows_phone = strpos($ua, 'Windows Phone') !== false;
			$ipad = strpos($ua, 'iPad') !== false;
			$carrier = $this->isCarrierMobile();

			switch (true) {
				case $android && $mobile:
				case $iphone:
				case $windows_phone:
					$class = self::DEVICE_CLASS_SMART; break;
				case $android:
				case $ipad:
					$class = self::DEVICE_CLASS_TABLET; break;
				case $carrier:
					$class =  self::DEVICE_CLASS_FEATURE; break;
				default:
					$class = self::DEVICE_CLASS_PC;
			}
			$this->device_class = $class;
		}
		if (isset($marge_classify[$this->device_class])) {
			return $marge_classify[$this->device_class];
		}
		return $this->device_class;
	}

	public function getCarrier()
	{
		if (isset($this->carrier)) return $this->carrier;
		
		$user_agent = $this->getUserAgent();
		switch (true) {
			case self::isNTT($user_agent):
				$this->carrier = self::CARRIER_NTT;
				break;
			case self::isKDDI($user_agent):
				$this->carrier = self::CARRIER_KDDI;
				break;
			case self::isSBM($user_agent):
				$this->carrier = self::CARRIER_SBM;
				break;
			case self::isWillcom($user_agent):
				$this->carrier = self::CARRIER_WILLCOM;
				break;
			default:
				$this->carrier = self::CARRIER_OTHER;
		}
		return $this->carrier;
	}

	public function getUID()
	{
		$not_supported = '__not_supported__';
		if (!isset($this->uid)) {
			switch ($this->getCarrier()) {
				case self::CARRIER_NTT:
					// iモードID (guid) ...7桁の英数字
					$this->uid = isset($_SERVER['HTTP_X_DCMGUID'])
						? $_SERVER['HTTP_X_DCMGUID']
						: $not_supported;
					break;
				case self::CARRIER_KDDI:
					// サブスクライバID ...29桁の英数字
					$this->uid = isset($_SERVER['HTTP_X_UP_SUBNO'])
						? $_SERVER['HTTP_X_UP_SUBNO']
						: $not_supported;
					break;
				case self::CARRIER_SBM:
					// X_JPHONE_UID ...16桁の英数字
					$this->uid = isset($_SERVER['HTTP_X_JPHONE_UID'])
						? $_SERVER['HTTP_X_JPHONE_UID']
						: $not_supported;
					break;
				default:
					$this->uid = $not_supported;
			}
		}
		return $this->uid == $not_supported ? null : $this->uid;
	}

	public function isCarrierMobile()
	{
		return $this->getCarrier() !== self::CARRIER_OTHER;
	}

	public function isCrawler()
	{
		if (!isset($this->crawlers)) {
			$this->crawlers = [
				'Googlebot',
				'Baiduspider',
				'bingbot',
				'Yeti',
				'NaverBot',
				'Yahoo',
				'Tumblr',
				'livedoor',
			];
		}
		foreach ($this->crawlers as $crawler) {
			if (Sx::x($this->user_agent)->contains($crawler)) return true;
		}
		return false;
	}

	public function referFromExternal($local_domain)
	{
		if (!isset($_SERVER["HTTP_REFERER"])) return false;
		$parts = parse_url($_SERVER["HTTP_REFERER"]);
		if (isset($parts['host'])) {
			return !Sx::x($parts['host'])->endsWith($local_domain);
		}
		return false;
	}

	public static function isNTT($user_agent)
	{
		return 0 < preg_match('/^DoCoMo/', $user_agent);
	}

	public static function isKDDI($user_agent)
	{
		switch (true) {
			case 0 < preg_match('/^KDDI-/', $user_agent):
			case 0 < preg_match('/^UP\.Browser/', $user_agent):
				return true;
			default:
				return false;
		}
	}

	public static function isSBM($user_agent)
	{
		switch (true) {
			case 0 < preg_match('/^SoftBank/', $user_agent):
			case 0 < preg_match('/^Semulator/', $user_agent):
			case 0 < preg_match('/^Vodafone/', $user_agent):
			case 0 < preg_match('/^Vemulator/', $user_agent):
			case 0 < preg_match('/^MOT-/', $user_agent):
			case 0 < preg_match('/^MOTEMULATOR/', $user_agent):
			case 0 < preg_match('/^J-PHONE/', $user_agent):
			case 0 < preg_match('/^J-EMULATOR/', $user_agent):
				return true;
			default:
				return false;
		}
	}

	public static function isWillcom($user_agent)
	{
		return 0 < preg_match('/^Mozilla\/3\.0\((?:DDIPOCKET|WILLCOM);/', $user_agent);
	}

}