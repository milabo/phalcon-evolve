<?php

namespace Phalcon\Evolve\Test\PrimitiveExtension;

use Phalcon\Evolve\Security\EmailAddress;
use Phalcon\Evolve\Test\UnitTestCase;


class EmailAddressTest extends UnitTestCase {

	public function testValid()
	{
		$this->assertTrue(EmailAddress::isValid('abcdefg.hijklmnopqrstuvwxyz!#$%&\'*/=?^_+-`{|}~0123456789@acme-inc.com'));
		// docomo変則
		$this->assertTrue(EmailAddress::isValid('valid.@docomo.ne.jp'));
		// au変則
		$this->assertTrue(EmailAddress::isValid('valid.@ezweb.ne.jp'));
	}

	public function testNotValid()
	{
		$this->assertFalse(EmailAddress::isValid('not-valid.@example.com'));
		$this->assertFalse(EmailAddress::isValid('not..valid@example.com'));
	}
	
} 