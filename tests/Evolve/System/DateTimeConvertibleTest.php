<?php

namespace Phalcon\Evolve\Test\System;

use Phalcon\Evolve\System\DateTimeConvertible;
use Phalcon\Evolve\Test\UnitTestCase;
use Phalcon\Config;

class DateTimeConvertibleTest extends UnitTestCase
{
	use DateTimeConvertible;

	const YMD = 'Y-m-d';
	const YMD_HIS = 'Y-m-d H:i:s';

	public function testTimestampToDatetime()
	{
		$result = self::timestampToDatetime(strtotime('2015-02-19'));
		$this->assertInstanceOf("DateTime", $result);
		$this->assertEquals('2015-02-19', $result->format(self::YMD));

		$result = self::timestampToDatetime(strtotime('2015-02-19 20:21:11'), 'Y/m/d H:i:s');
		$this->assertEquals("2015/02/19 20:21:11", $result);

		$result = self::timestampToDatetime(null, 'Y/m/d');
		$this->assertEquals('-', $result);

		$result = self::timestampToDatetime(null, 'H:i:s', '@');
		$this->assertEquals('@', $result);
	}

	public function testAnyToDatetime()
	{
		$result = self::anyToDatetime(new \DateTime('2015-04-01 21:12:42'));
		$this->assertInstanceOf("DateTime", $result);
		$this->assertEquals('2015-04-01 21:12:42', $result->format(self::YMD_HIS));

		$result = self::anyToDatetime(new \DateTime('2015-04-01 21:12:42'), self::YMD);
		$this->assertEquals('2015-04-01', $result);

		$result = self::anyToDatetime(' 2014-11-23 01:12:52 ');
		$this->assertInstanceOf("DateTime", $result);
		$this->assertEquals('2014-11-23 01:12:52', $result->format(self::YMD_HIS));

		$result = self::anyToDatetime(strtotime('2014-11-23 01:12:52'));
		$this->assertInstanceOf("DateTime", $result);
		$this->assertEquals('2014-11-23 01:12:52', $result->format(self::YMD_HIS));

		$result = self::anyToDatetime('2014-11-23 01:12:52', 'Y年m月d日');
		$this->assertEquals('2014年11月23日', $result);

		$this->assertNull(self::anyToDatetime(null));
		$this->assertEquals('-', self::anyToDatetime(null, self::YMD));
		$this->assertEquals('@', self::anyToDatetime(null, self::YMD, '@'));
	}

	public function testAnyToTimestamp()
	{
		$result = self::anyToTimestamp(new \DateTime('2014-11-23 01:12:52'));
		$this->assertEquals('2014-11-23 01:12:52', date(self::YMD_HIS, $result));

		$result = self::anyToTimestamp(' 2014-11-23 01:12:52 ');
		$this->assertEquals('2014-11-23 01:12:52', date(self::YMD_HIS, $result));

		$result = self::anyToTimestamp(strtotime('2014-11-23 01:12:52'));
		$this->assertEquals('2014-11-23 01:12:52', date(self::YMD_HIS, $result));

		$this->assertNull(self::anyToTimestamp(null));
	}

	public function testFormatDateForSave()
	{
		$result = self::formatDateForSave(new \DateTime('2014-11-23 01:12:52'));
		$this->assertEquals('2014-11-23', $result);

		$result = self::formatDateForSave('2014-11-23 01:12:52');
		$this->assertEquals('2014-11-23', $result);

		$result = self::formatDateForSave(strtotime('2014-11-23 01:12:52'));
		$this->assertEquals('2014-11-23', $result);
	}

	public function testDateRange()
	{
		$gen = self::dateRange('2015-10-01', '2015-10-03');
		$cat = "";
		foreach ($gen as $date) {
			$cat .= $date . " ";
		}
		$this->assertEquals('2015-10-01 2015-10-02 2015-10-03 ', $cat);
	}

	public function testYearMonthRange()
	{
		$gen = self::yearMonthRange('2015-05-01', '2015-07-01');
		$cat = "";
		foreach ($gen as $ym) {
			$cat .= $ym . " ";
		}
		$this->assertEquals('2015-05 2015-06 2015-07 ', $cat);
	}

	public function testToDeemedBirthday()
	{
		$result = self::toDeemedBirthday(new \DateTime('2014-11-23'));
		$this->assertInstanceOf('DateTime', $result);
		$this->assertEquals('2014-11-23', $result->format(self::YMD));

		$result = self::toDeemedBirthday(new \DateTime('2012-02-29'));
		$this->assertInstanceOf('DateTime', $result);
		$this->assertEquals('2012-02-28', $result->format(self::YMD));

		$result = self::toDeemedBirthday('2016-02-29', self::YMD);
		$this->assertEquals('2016-02-28', $result);
	}

}