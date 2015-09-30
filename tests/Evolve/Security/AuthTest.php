<?php

namespace Test\Components\Security;

use Phalcon\Evolve\Security\Auth;
use Phalcon\Evolve\Security\UserInterface;
use Phalcon\DI;
use Phalcon\Config;
use Phalcon\Session\Adapter as Session;

class AuthTest extends \UnitTestCase {
	
	public function testAuthenticate()
	{
		$user = new UserMock();
		
		// 認証前
		$auth = Auth::load();
		$this->assertFalse($auth->isAuthenticated());
		$this->assertNull($auth->getUser());
		
		// 認証登録
		$auth->register($user, false);
		$this->assertTrue($auth->isAuthenticated());
		$this->assertNotNull($auth->getUser());
		
		// 認証確認
		$auth = Auth::load();
		$auth->load();
		$this->assertTrue($auth->isAuthenticated());
		$this->assertEquals($user, $auth->getUser());
		
		// 認証クリア
		$auth->clear(false);
		$this->assertFalse($auth->isAuthenticated());
		$this->assertNull($auth->getUser());
		
		// 認証クリア確認
		$auth = Auth::load();
		$this->assertFalse($auth->isAuthenticated());
		$this->assertNull($auth->getUser());
	}

} 

class UserMock implements UserInterface {

	public function getId()
	{
		return PHP_INT_MAX - 1;
	}
	
	public function serialize()
	{
		return serialize($this);
	}
	
	public function unserialize($str)
	{
		return unserialize($str);
	}
	
	public function isEnabled()
	{
		return true;
	}
	
	public function getRole()
	{
		return 'test-user-mock';
	}
	
	public function eraseCredentials()
	{
		return $this;
	}
	
	public function getSessionIndexerId()
	{
		return $this->getRole() . ':auth-test';
	}
	
}