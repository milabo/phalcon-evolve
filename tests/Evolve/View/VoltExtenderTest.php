<?php

namespace Test\Components\View;


use Phalcon\Evolve\View\VoltExtender;

class VoltExtenderTest extends \UnitTestCase {
	
	public function testFilter()
	{
		$this->assertEquals("\\1,000", VoltExtender::yen(1000));
		$this->assertEquals("test\n", VoltExtender::lf("test"));
		$this->assertEquals("test\r", VoltExtender::cr("test"));
		$this->assertEquals("test\r\n", VoltExtender::crlf("test"));
		$this->assertEquals(
			"This is a test string.",
			VoltExtender::tw("This is a test string.", 22),
			'規定の最大長。そのまま出力。'
		);
		$this->assertEquals(
			'<span style="letter-spacing: -1px;" data-strwidth="22">This is a test string.</span>',
			VoltExtender::tw("This is a test string.", 21),
			'最大文字数1文字オーバー。字詰スタイルを適用。'
		);
		$this->assertEquals(
			'<span style="letter-spacing: -1px;" data-strwidth="22">This is a test string.</span>',
			VoltExtender::tw("This is a test string.", 18),
			'最大文字数4文字オーバー。字詰スタイルを適用。'
		);
		$this->assertEquals(
			'<span style="letter-spacing: -1px;" data-strwidth="22">This is a test s…</span>',
			VoltExtender::tw("This is a test string.", 17),
			'最大文字数5文字オーバー。字詰スタイルを適用しつつ超過分カット。'
		);
		$this->assertEquals(
			"This is<br/> a test<br/> string.",
			VoltExtender::nlbr("This is\r\n a test\r string."),
			'改行文字を改行エレメントに変換。'
		);
	}
	
	public function testFunction()
	{
		$this->assertEquals('', VoltExtender::queryString());
		$this->assertEquals(
			'?add_key=add_value',
			VoltExtender::queryString(array('add_key' => 'add_value'))
		);
		
		$_GET['get_key'] = 'get_value';
		$this->assertEquals(
			'?get_key=alt_value&add_key=add_value',
			VoltExtender::queryString(array('get_key' => 'alt_value', 'add_key' => 'add_value'))
		);
		unset ($_GET['get_key']);
		
		$var = 'test';
		$empty = '';
		$nothing = null;
		$this->assertEquals('test', VoltExtender::getOrDefault($var, '-'));
		$this->assertEquals('', VoltExtender::getOrDefault($empty, '-'));
		$this->assertEquals('-', VoltExtender::getOrDefault($nothing, '-'));
		
		$arr = array( 'exist_key' => 'exist_value' );
		$this->assertEquals('exist_value', VoltExtender::searchOrDefault($arr, 'exist_key', '-'));
		$this->assertEquals('-', VoltExtender::searchOrDefault($arr, 'nothing_key', '-'));
	}

} 