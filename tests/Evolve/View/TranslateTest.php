<?php

namespace Phalcon\Evolve\Test\View;

use Phalcon\Evolve\View\Translate;
use Phalcon\Config;
use Phalcon\DiInterface;

class TranslateTest extends \UnitTestCase {
	
	/** @var string translations directory for test */
	private $t_dir;
	
	public function setUp(DiInterface $di = NULL, Config $config = NULL)
	{
		parent::setUp($di, $config);
		
		$en = <<< EOS
# メッセージ
hello,"Hello!"
welcome,"Welcome to %dic.city% city, %name%."
# 辞書
dic.male,"male"
dic.female,"female"
dic.city,"Sample"
EOS;

		$it = <<< EOS
# メッセージ
hello,"Ciao!"
welcome,"Benvenuti a città %dic.city%, %name%."
# 辞書
dic.male,"maschio"
dic.female,"femminile"
dic.city,"Esempio"
EOS;
		
		$this->t_dir = $this->temp_dir . '/translations';
		@mkdir($this->t_dir);
		file_put_contents("{$this->t_dir}/en.csv", $en);
		file_put_contents("{$this->t_dir}/it.csv", $it);
	}
	
	public function tearDown()
	{
		system("rm -rf {$this->t_dir}");
	}
	
	public function testTranslate()
	{
		// 英語
		$t = new Translate($this->t_dir, 'en');
		$this->assertEquals('en', $t->getLanguage());
		$this->assertTrue($t->exists('hello'));
		$this->assertFalse($t->exists('bye'));
		$this->assertEquals('Hello!', $t->_('hello'));
		$this->assertEquals('Welcome to Sample city, Tester.', $t->_('welcome', ['name' => 'Tester']));
		$this->assertEquals('male', $t->dic('male'));
		$this->assertEquals('Female', $t->dic('female', true));
		
		// イタリア語
		$t->switchLanguage('it');
		$this->assertEquals('it', $t->getLanguage());
		$this->assertTrue($t->exists('hello'));
		$this->assertFalse($t->exists('bye'));
		$this->assertEquals('Ciao!', $t->_('hello'));
		$this->assertEquals('Benvenuti a città Esempio, Tester.', $t->_('welcome', ['name' => 'Tester']));
		$this->assertEquals('maschio', $t->dic('male'));
		$this->assertEquals('Femminile', $t->dic('female', true));
	}

} 