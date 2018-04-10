<?php

namespace Phalcon\Evolve\Test\View;


use Phalcon\Evolve\Model\ModelBase;
use Phalcon\Evolve\Test\UnitTestCase;

class ModelBaseTest extends UnitTestCase {

	public function testValidate()
	{
		$sample = [
			'id' => 123,
			'name1' => 'hoge',
			'name2' => '',
			'date1' => '2012-01-02',
			'date2' => '',
			'date3' => '2012-1-3',
		];
		$this->assertEmpty(ModelBase::_validate([], []));
		$this->assertEmpty(ModelBase::_validate([], $sample));

		$this->assertEmpty(ModelBase::_validate([
			'id' => [
				'required' => true,
				'min_value' => 123,
				'max_value' => 123,
			],
			'name1' => [
				'required' => true,
				'min_length' => 4,
				'max_length' => 4,
			],
			'name2' => [
				'required' => false,
			],
			'date1' => [
				'required' => true,
				'pattern' => '/\d{4}-\d{2}-\d{2}/',
			],
			'date2' => [
				'required' => false,
				'pattern' => '/\d{4}-\d{2}-\d{2}/',
			],
		], $sample));

		$this->assertEquals([
			'id' => ['min_value:124', 'max_value:122'],
			'name1' => ['min_length:5', 'max_length:3'],
			'name2' => ['required'],
			'date3' => ['pattern'],
		], ModelBase::_validate([
			'id' => [
				'required' => true,
				'min_value' => 124,
				'max_value' => 122,
			],
			'name1' => [
				'required' => true,
				'min_length' => 5,
				'max_length' => 3,
			],
			'name2' => [
				'required' => true,
			],
			'date3' => [
				'required' => false,
				'pattern' => '/\d{4}-\d{2}-\d{2}/',
			],
		], $sample));
	}

} 