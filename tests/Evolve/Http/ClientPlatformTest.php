<?php

namespace Test\Components\Http;

use Phalcon\Evolve\Http\ClientPlatform;

class ClientPlatformTest extends \UnitTestCase {
	
	/**
	 * UA 情報は 2014年 無作為に選定
	 */
	const
		UA_FP_DoCoMo_F906i = 'DoCoMo/2.0 F906i',
		UA_FP_KDDI_W63CA = 'KDDI-CA3C UP.Browser/6.2_7.2.7.1.K.2.232 (GUI) MMP/2.0',
		UA_FP_SBM_002P = 'SoftBank/1.0/002P/PJP10[/Serial] Browser/NetFront/3.4 Profile/MIDP-2.0 Configuration/CLDC-1.1',
		UA_FP_Willcom_WX331K = 'Mozilla/3.0(WILLCOM;KYOCERA/WX331K/2;1.0.3.13.000000/0.1/C100)Opera 7.2 EX',
		UA_SP_SBM_005SH = 'Mozilla/5.0 (Linux; U; Android 2.2.1; ja-jp; SBM005SH Build/S0500) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1',
		UA_SP_WindowsPhone = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows Phone OS 7.5; Trident/5.0; IEMobile/9.0; FujitsuToshibaMobileCommun; IS12T; KDDI)',
		UA_SP_iPhone = 'Mozilla/5.0 (iPhone; CPU iPhone OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A403 Safari/8536.25',
		UA_TB_iPad = 'Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A403 Safari/8536.25',
		UA_TB_DoCoMo_L06C = 'Mozilla/5.0 (Linux; U; Android 3.0; ja-jp; L-06C Build/HRI39) AppleWebKit/534.13 (KHTML, like Gecko) Version/4.0 Safari/534.13',
		UA_TB_KDDI_FJT21 = 'Mozilla/5.0 (Linux; U; Android 4.2.2; ja-jp; FJT21 Build/V09R44D) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Safari/534.30',
		UA_PC_IE = 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko',
		UA_PC_CHROME = 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.114 Safari/537.36',
		UA_PC_SAFARI = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.76.4 (KHTML, like Gecko) Version/7.0.4 Safari/537.76.4';
	
	public function testCarriers()
	{
		$this->assertTrue(
			ClientPlatform::isNTT(self::UA_FP_DoCoMo_F906i)
		);
		$this->assertTrue(
			ClientPlatform::isKDDI(self::UA_FP_KDDI_W63CA)
		);
		$this->assertTrue(
			ClientPlatform::isSBM(self::UA_FP_SBM_002P)
		);
		$this->assertTrue(
			ClientPlatform::isWillcom(self::UA_FP_Willcom_WX331K)
		);
		$this->assertFalse(
			ClientPlatform::isNTT(self::UA_FP_KDDI_W63CA)
		);
		$this->assertFalse(
			ClientPlatform::isKDDI(self::UA_FP_SBM_002P)
		);
		$this->assertFalse(
			ClientPlatform::isSBM(self::UA_FP_Willcom_WX331K)
		);
	}
	
	public function testFpPlatform()
	{	
		// NTT
		$_SERVER['HTTP_X_DCMGUID'] = 'afe487n';
		$client = new ClientPlatform(self::UA_FP_DoCoMo_F906i);
		$this->assertTrue($client->isCarrierMobile());
		$this->assertEquals(
			ClientPlatform::DEVICE_CLASS_FEATURE,
			$client->getDeviceClass()
		);
		$this->assertEquals(
			ClientPlatform::CARRIER_NTT,
			$client->getCarrier()
		);
		$this->assertEquals(
			'afe487n',
			$client->getUID()
		);
		unset($_SERVER['HTTP_X_DCMGUID']);
		
		// KDDI
		$_SERVER['HTTP_X_UP_SUBNO'] = '12345678901234_56.ezweb.ne.jp';
		$client = new ClientPlatform(self::UA_FP_KDDI_W63CA);
		$this->assertTrue($client->isCarrierMobile());
		$this->assertEquals(
			ClientPlatform::DEVICE_CLASS_FEATURE,
			$client->getDeviceClass()
		);
		$this->assertEquals(
			ClientPlatform::CARRIER_KDDI,
			$client->getCarrier()
		);
		$this->assertEquals(
			'12345678901234_56.ezweb.ne.jp',
			$client->getUID()
		);
		unset($_SERVER['HTTP_X_UP_SUBNO']);
		
		// SBM
		$_SERVER['HTTP_X_JPHONE_UID'] = 'cnejf7873mk9whj3';
		$client = new ClientPlatform(self::UA_FP_SBM_002P);
		$this->assertTrue($client->isCarrierMobile());
		$this->assertEquals(
			ClientPlatform::DEVICE_CLASS_FEATURE,
			$client->getDeviceClass()
		);
		$this->assertEquals(
			ClientPlatform::CARRIER_SBM,
			$client->getCarrier()
		);
		$this->assertEquals(
			'cnejf7873mk9whj3',
			$client->getUID()
		);
		unset($_SERVER['HTTP_X_JPHONE_UID']);
	}
	
	public function testSpPlatform()
	{
		// iPhone
		$client = new ClientPlatform(self::UA_SP_iPhone);
		$this->assertFalse($client->isCarrierMobile());
		$this->assertEquals(
			ClientPlatform::DEVICE_CLASS_SMART,
			$client->getDeviceClass()
		);
		
		// 005SH
		$client = new ClientPlatform(self::UA_SP_SBM_005SH);
		$this->assertFalse($client->isCarrierMobile());
		$this->assertEquals(
			ClientPlatform::DEVICE_CLASS_SMART,
			$client->getDeviceClass()
		);
		
		// Windows Phone
		$client = new ClientPlatform(self::UA_SP_WindowsPhone);
		$this->assertFalse($client->isCarrierMobile());
		$this->assertEquals(
			ClientPlatform::DEVICE_CLASS_SMART,
			$client->getDeviceClass()
		);
	}
	
	public function testTbPlatform()
	{
		// L06C
		$client = new ClientPlatform(self::UA_TB_DoCoMo_L06C);
		$this->assertFalse($client->isCarrierMobile());
		$this->assertEquals(
			ClientPlatform::DEVICE_CLASS_TABLET,
			$client->getDeviceClass()
		);
		
		// FJT21
		$client = new ClientPlatform(self::UA_TB_KDDI_FJT21);
		$this->assertFalse($client->isCarrierMobile());
		$this->assertEquals(
			ClientPlatform::DEVICE_CLASS_TABLET,
			$client->getDeviceClass()
		);
		
		// iPad
		$client = new ClientPlatform(self::UA_TB_iPad);
		$this->assertFalse($client->isCarrierMobile());
		$this->assertEquals(
			ClientPlatform::DEVICE_CLASS_TABLET,
			$client->getDeviceClass()
		);
	}
	
	public function testPcPlatform()
	{
		// IE
		$client = new ClientPlatform(self::UA_PC_IE);
		$this->assertFalse($client->isCarrierMobile());
		$this->assertEquals(
			ClientPlatform::DEVICE_CLASS_PC,
			$client->getDeviceClass()
		);
		
		// Chrome
		$client = new ClientPlatform(self::UA_PC_CHROME);
		$this->assertFalse($client->isCarrierMobile());
		$this->assertEquals(
			ClientPlatform::DEVICE_CLASS_PC,
			$client->getDeviceClass()
		);

		// Safari
		$client = new ClientPlatform(self::UA_PC_SAFARI);
		$this->assertFalse($client->isCarrierMobile());
		$this->assertEquals(
			ClientPlatform::DEVICE_CLASS_PC,
			$client->getDeviceClass()
		);
	}

	/**
	 * デバイスの分類をマージするテスト
	 */
	public function testMargePlatform()
	{
		// マージテーブルを用意
		$marge_platform = [
			ClientPlatform::DEVICE_CLASS_SMART => ClientPlatform::DEVICE_CLASS_PC,
			ClientPlatform::DEVICE_CLASS_TABLET => ClientPlatform::DEVICE_CLASS_PC,
		];
		
		// iPhone (スマホ) -> PC
		$client = new ClientPlatform(self::UA_SP_iPhone);
		$this->assertEquals(
			ClientPlatform::DEVICE_CLASS_PC,
			$client->getDeviceClass($marge_platform)
		);
		
		// iPad (タブレット) -> PC
		$client = new ClientPlatform(self::UA_TB_iPad);
		$this->assertEquals(
			ClientPlatform::DEVICE_CLASS_PC,
			$client->getDeviceClass($marge_platform)
		);
		
		// W63CA (ガラケー) -> マージされずガラケーに分類
		$client = new ClientPlatform(self::UA_FP_KDDI_W63CA);
		$this->assertEquals(
			ClientPlatform::DEVICE_CLASS_FEATURE,
			$client->getDeviceClass($marge_platform)
		);
	}

} 