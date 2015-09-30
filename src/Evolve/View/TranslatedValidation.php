<?php

namespace Phalcon\Evolve\View;

use Phalcon\Validation,
	Phalcon\Validation\Message;
use Phalcon\Validation\Validator\PresenceOf,
	Phalcon\Validation\Validator\Email,
	Phalcon\Validation\Validator\Between,
	Phalcon\Validation\Validator\Confirmation,
	Phalcon\Validation\Validator\InclusionIn,
	Phalcon\Validation\Validator\Regex,
	Phalcon\Validation\Validator\StringLength,
	Phalcon\Validation\Validator\Db\Uniqueness;
use Phalcon\Evolve\PrimitiveExtension\StringExtension as Sx;
use Phalcon\Evolve\PrimitiveExtension\ArrayExtension as Ax;

/**
 * Translate を使ってエラーメッセージを出力するバリデータ
 * @package Phalcon\Evolve\View
 */
class TranslatedValidation extends Validation {
	
	/** @type Translate */
	protected $translate;

	/**
	 * @param Translate $translate
	 * @param array|null $validators
	 */
	public function __construct($translate, $validators = null)
	{
		parent::__construct($validators);
		$this->translate = $translate;
	}

	public function getMessages()
	{
		return $this->mapMessages(function(Message $message) {
			$type = Sx::x($message->getType())->toSnakeCase();
			$text = $this->translate->query("err.validate.$type", [
				'field' => $this->getLabel($message->getField()),
				'value' => $this->getValue($message->getField()),
				'additional' => $message->getMessage(),
			]);
			$message->setMessage($text);
			return $message;
		});
	}

	/**
	 * メッセージオブジェクトを文字列に変換した配列を取得
	 * @return array
	 */
	public function getMessageTexts()
	{
		return array_map(function(Message $message) {
			return $message->getMessage();
		}, $this->getMessages());
	}

	/**
	 * @param string $glue
	 * @return string
	 */
	public function getMessageTextsString($glue)
	{
		return implode($glue, $this->getMessageTexts());
	}

	/**
	 * @param callable $callback
	 * @return array
	 */
	private function mapMessages(callable $callback)
	{
		$results = [];
		foreach (parent::getMessages() as $message) {
			$results[] = $callback($message);
		}
		return $results;
	}

	/**
	 * @param $field
	 * @param $label
	 * @return self $this
	 */
	public function setLabel($field, $label)
	{
		if (!isset($this->_labels)) {
			$this->_labels = [];
		}
		$this->_labels[$field] = $label;
		return $this;
	}
	
	#region short hand and utility

	/**
	 * テキストフィールドの検証を設定するショートハンド
	 * @param string $attribute
	 * @param string $label
	 * @param int $minLength
	 * @param int $maxLength
	 * @param bool $required
	 * @return self $this
	 */
	public function textField($attribute, $label, $minLength = 0, $maxLength = 0, $required = false)
	{
		$this->setFilters($attribute, ['trim', 'string']);
		if ($required) {
			$this->add($attribute, new PresenceOf([
				'cancelOnFail' => true,
			]));
		}
		$options = [];
		$message = "";
		if ($minLength > 0) {
			$options['min'] = $minLength;
			$options['messageMinimum'] = $this->translate->minLength($minLength);
			$message .= $this->translate->minLength($minLength);
		}
		if ($maxLength > 0) {
			$options['max'] = $maxLength;
			$options['messageMaximum'] = $this->translate->maxLength($maxLength);
			$message .= $this->translate->maxLength($maxLength);
		}
		if ($message != "") {
			$options['message'] = $message;
			$this->add($attribute, new StringLength($options));
		}
		$this->setLabel($attribute, $label);
		return $this;
	}

	/**
	 * ユーザIDの検証を設定するショートハンド
	 * @param string $attribute
	 * @param string $label
	 * @param string $table
	 * @param string $column
	 * @param \Phalcon\Db\AdapterInterface $db
	 * @return self $this
	 */
	public function loginField($attribute, $label, $table, $column, $db = null)
	{
		$db or $db = $this->di->get('db');
		$this
			->add($attribute, new PresenceOf([
				'cancelOnFail' => true,
			]))
			->add($attribute, new Regex([
				'pattern' => '/^[a-zA-Z0-9_\-]+$/',
				'message' => $this->translate->query('gen.identity_chars'),
			]))
			->add($attribute, new Uniqueness([
				'table' => $table,
				'column' => $column,
			], $db));
		$this->setLabel($attribute, $label);
		return $this;
	}

	/**
	 * パスワード設定フィールドの検証を設定するショートハンド
	 * @param string $attribute
	 * @param string $attribute_confirm
	 * @param int $minLength
	 * @param int $maxLength
	 * @return self $this
	 */
	public function passwordAndConfirmField($attribute, $attribute_confirm, $minLength = 8, $maxLength = 60)
	{
		$this
			->setFilters($attribute, ['trim', 'string'])
			->setFilters($attribute_confirm, ['trim', 'string'])
			->add($attribute, new PresenceOf([
				'cancelOnFail' => true
			]))
			->add($attribute_confirm, new PresenceOf([
				'cancelOnFail' => true
			]))
			->add($attribute, new Confirmation([
				'with' => $attribute_confirm
			]))
			;
		$options = Ax::zero();
		if ($minLength > 0) {
			$options['min'] = $minLength;
			$options['messageMinimum'] = $this->translate->minLength($minLength);
		}
		if ($maxLength > 0) {
			$options['max'] = $maxLength;
			$options['messageMaximum'] = $this->translate->maxLength($maxLength);
		}
		if ($options->any()) {
			$this->add($attribute, new StringLength($options->unwrap()));
		}
		$this->setLabel($attribute, $this->translate->dic('password'));
		$this->setLabel($attribute_confirm, $this->translate->dic('password_confirm'));
		return $this;
	}

	public function emailField($attribute, $label, $required = false)
	{
		$this->setFilters($attribute, ['trim', 'string']);
		if ($required) {
			$this->add($attribute, new PresenceOf());
		}
		$this->add($attribute, new Email());
		$this->setLabel($attribute, $label);
		return $this;
	}

	/**
	 * 数値フィールドの検証を設定するショートハンド
	 * @param $attribute
	 * @param string $label
	 * @param null $minValue
	 * @param null $maxValue
	 * @param bool $required
	 * @return self $this
	 */
	public function numberField($attribute, $label, $minValue = null, $maxValue = null, $required = false)
	{
		$this->setFilters($attribute, ['int']);
		if ($required) {
			$this->add($attribute, new PresenceOf());
		}
		$options = Ax::zero();
		if (!is_null($minValue)) {
			$options['minimum'] = $minValue;
			$options['messageMinimum'] = $this->translate->minValue($minValue);
		}
		if (!is_null($maxValue)) {
			$options['maximum'] = $maxValue;
			$options['messageMaximum'] = $this->translate->minLength($maxValue);
		}
		if ($options->any()) {
			$this->add($attribute, new Between($options->unwrap()));
		}
		$this->setLabel($attribute, $label);
		return $this;
	}

	/**
	 * @param $attribute
	 * @param $label
	 * @param bool $required
	 * @return self $this
	 */
	public function postalCodeField($attribute, $label, $required = false)
	{
		if ($required) {
			$this->add($attribute, new PresenceOf());
		}
		$this->setFilters($attribute, 'separated_number');
		$this->add($attribute, new Regex([
			'pattern' => '/^[0-9]{7}$/',
			'message' => $this->translate->query('gen.length', ['value' => 7]),
		]));
		$this->setLabel($attribute, $label);
		return $this;
	}

	/**
	 * enum など配列に含むかどうかの検証を設定するショートハンド
	 * @param string $attribute
	 * @param string $label
	 * @param array $domain
	 * @param string $domain_name
	 * @param array $filters
	 * @return $this
	 */
	public function inclusionIn($attribute, $label, array $domain, $domain_name, array $filters = null)
	{
		if (isset($filters)) {
			$this->setFilters($attribute, $filters);
		}
		$this
			->add($attribute, new PresenceOf([
				'cancelOnFail' => true
			]))
			->add($attribute, new InclusionIn([
			'domain' => $domain,
			'message' => $domain_name,
		]));
		$this->setLabel($attribute, $label);
		return $this;
	}
	#endregion

} 