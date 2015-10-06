<?php

namespace Phalcon\Evolve\Model;

use Phalcon\Mvc\Model\Behavior,
	Phalcon\Mvc\Model\BehaviorInterface,
	Phalcon\Evolve\PrimitiveExtension\StringExtension as Sx;

/**
 * フィールドを gzip 圧縮して永続化するビヘイビア
 * キャッシュ機構として ModelBase::setVolatile, getVolatile を使用する
 * @package Phalcon\Evolve\Orm
 */
class GzCompressBehavior extends Behavior implements BehaviorInterface {

	/**
	 * @type array|\string[]
	 */
	private $fields;
	private $level = -1;
	
	/**
	 * @param string[] $fields 圧縮するフィールド名を格納した配列
	 * @param integer $level 圧縮レベル デフォルトで zlib ライブラリのデフォルト値
	 * @param array $options
	 */
	public function __construct(array $fields, $level = -1, $options = null)
	{
		parent::__construct($options);
		$this->fields = $fields;
	}

	/**
	 * @param string $type
	 * @param \App\Models\ModelBase $model
	 */
	public function notify($type, $model)
	{
		switch ($type) {
			case 'beforeSave':
				foreach ($this->fields as $field) {
					$value = $this->getValue($model, $field);
					$model->setVolatile("gzcompress-$field", $value);
					$this->setValue($model, $field, \gzcompress($value, $this->level));
				}
				break;
			case 'afterSave':
				foreach ($this->fields as $field) {
					$value = $model->getVolatile("gzcompress-$field");
					$model->deleteVolatile("gzcompress-$field");
					$this->setValue($model, $field, $value);
				}
				break;
			case 'afterFetch':
				foreach ($this->fields as $field) {
					$value = $this->getValue($model, $field);
					$this->setValue($model, $field, \gzuncompress($value));
				}
				break;
		}
	}

	/**
	 * @param \App\Models\ModelBase $model
	 * @param string $field
	 * @return mixed
	 * @throws \ErrorException
	 */
	private function getValue($model, $field)
	{
		$getter = "get" . Sx::x($field)->toPascalCase();
		if (method_exists($model, $getter)) {
			return $model->$getter();
		} else if (property_exists($model, $field)) {
			return $model->$field;
		} else {
			throw new \ErrorException("field $field not found on class " . get_class($model));
		}
	}

	/**
	 * @param \App\Models\ModelBase $model
	 * @param string $field
	 * @param mixed $value
	 * @throws \ErrorException
	 */
	private function setValue($model, $field, $value)
	{
		$setter = "set" . Sx::x($field)->toPascalCase();
		if (method_exists($model, $setter)) {
			$model->$setter($value);
		} else if (property_exists($model, $field)) {
			$model->$field = $value;
		} else {
			throw new \ErrorException("field $field not found on class " . get_class($model));
		}
	}

} 