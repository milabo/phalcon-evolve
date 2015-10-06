<?php
/**
 * Author: Kenta Suzuki
 * Since: 2014/11/30 22:32
 * Copyright: 2014 sukobuto.com All Rights Reserved.
 */

namespace Phalcon\Evolve\Model;

use Phalcon\Mvc\Model\Behavior,
	Phalcon\Mvc\Model\BehaviorInterface,
	Phalcon\Evolve\PrimitiveExtension\StringExtension as Sx;

/**
 * フィールドを serialize して永続化するビヘイビア
 * キャッシュ機構として ModelBase::setVolatile, getVolatile を使用する
 * @package Phalcon\Evolve\Orm
 */
class SerializeBehavior extends Behavior implements BehaviorInterface {

	/**
	 * @type array|\string[]
	 */
	private $fields;
	
	/**
	 * @param string[] $fields 圧縮するフィールド名を格納した配列
	 * @param array $options
	 */
	public function __construct(array $fields, $options = null)
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
					$model->setVolatile("serialize-$field", $value);
					$this->setValue($model, $field, serialize($value));
				}
				break;
			case 'afterSave':
				foreach ($this->fields as $field) {
					$value = $model->getVolatile("serialize-$field");
					$model->deleteVolatile("serialize-$field");
					$this->setValue($model, $field, $value);
				}
				break;
			case 'afterFetch':
				foreach ($this->fields as $field) {
					$value = $this->getValue($model, $field);
					$this->setValue($model, $field, unserialize($value));
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