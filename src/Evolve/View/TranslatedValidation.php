<?php

namespace Phalcon\Evolve\View;

use Phalcon\Evolve\Security\EmailAddress;
use Phalcon\Validation,
	Phalcon\Validation\Message;
use Phalcon\Validation\Validator\PresenceOf,
	Phalcon\Validation\Validator\Between,
	Phalcon\Validation\Validator\Confirmation,
	Phalcon\Validation\Validator\InclusionIn,
	Phalcon\Validation\Validator\Regex,
	Phalcon\Validation\Validator\StringLength,
	Phalcon\Validation\Validator\Db\Uniqueness;
use Phalcon\Evolve\PrimitiveExtension\StringExtension as Sx;
use Phalcon\Evolve\PrimitiveExtension\ArrayExtension as Ax;

/**
 * Class ValidatorBase
 * @package Phalcon\Evolve\View
 */
class ValidatorBase extends Validation\Validator {

	/**
	 * デフォルトを設定するためにオーバーライド
	 * @param string|array $keys
	 * @return mixed
	 * @throws \Exception
	 */
	public function getOption($keys)
	{
		if (is_array($keys)) {
			$default = $keys['default'];
			$key = $keys['key'];
		} else if (is_string($keys)) {
			$key = $keys;
		} else {
			throw new \Exception('$keys (' . gettype($keys) .  ') is not valid type.');
		}
		$value = parent::getOption($key);
		if (is_null($value) && isset($default)) return $default;
		else return $value;
	}

	protected function prepareLabel($validator, $attribute)
	{
		$label = $this->getOption("label");
		if (is_array($label)) {
			$label = $label[$attribute];
		}
		if (empty($label)) {
			$label = $validator->getLabel($attribute);
		}
		return $label;
	}

	protected function prepareMessage($validator, $attribute, $type, $option = "message")
	{
		$message = $this->getOption($option);
		if (is_array($message)) {
			$message = $message[$attribute];
		}
		if (empty($message)) {
			$message = $validator->getDefaultMessage($type);
		}
		return $message;
	}

	protected function prepareCode($attribute)
	{
		$code = $this->getOption("code");
		if (is_array($code)) {
			$code = $code[$attribute];
		}
		return $code;
	}

	/**
	 * フィールド名のみ設定してシンプルなメッセージを追加する
	 * @param $validator
	 * @param $attribute
	 * @param $type 'err.validate.$type'
	 */
	protected function appendMessageSimply($validator, $attribute, $type)
	{
		$label = $this->prepareLabel($validator, $attribute);
		$message = $this->prepareMessage($validator, $attribute, $type);
		$code = $this->prepareCode($attribute);

		$replacePairs = [":field" => $label];

		$validator->appendMessage(
			new Message(
				strtr($message, $replacePairs),
				$attribute,
				$type,
				$code
			)
		);
	}

	public function validate($validator, $attribute)
	{
		throw new \Exception('Unimplemented.');
	}
}

/**
 * Class Email
 * Phalcon 標準の Email validator が RFC に違反するメールアドレスで無応答障害が発生するため上書き実装
 * @package Phalcon\Evolve\View
 */
class Email extends Validation\Validator {

	protected function prepareLabel($validator, $attribute)
	{
		$label = $this->getOption("label");
		if (is_array($label)) {
			$label = $label[$attribute];
		}
		if (empty($label)) {
			$label = $validator->getLabel($attribute);
		}
		return $label;
	}

	protected function prepareMessage($validator, $attribute, $type, $option = "message")
	{
		$message = $this->getOption($option);
		if (is_array($message)) {
			$message = $message[$attribute];
		}
		if (empty($message)) {
			$message = $validator->getDefaultMessage($type);
		}
		return $message;
	}

	protected function prepareCode($attribute)
	{
		$code = $this->getOption("code");
		if (is_array($code)) {
			$code = $code[$attribute];
		}
		return $code;
	}

	public function validate($validator, $attribute)
	{
		$value = $validator->getValue($attribute);

		if (!EmailAddress::isValid($value)) {
			$label = $this->prepareLabel($validator, $attribute);
			$message = $this->prepareMessage($validator, $attribute, "Email");
			$code = $this->prepareCode($attribute);

			$replacePairs = [":field" => $label];

			$validator->appendMessage(
				new Message(
					strtr($message, $replacePairs),
					$attribute,
					"Email",
					$code
				)
			);
			return false;
		}
		return true;
	}

}

/**
 * Class Date
 * @package Phalcon\Evolve\View
 *
 * <code>
 * // most simply
 * $validator->add('date', new Date([
 *		'year_attribute' => 'year',
 *		'month_attribute' => 'month',
 *		'day_attribute' => 'day'
 * ]));
 *
 * $validator->add('date', new Date([
 * 		'required' => true,
 *		'year_attribute' => 'year',
 *		'month_attribute' => 'month',
 *		'day_attribute' => 'day',
 * 		'message.presence_of' => 'choose_one'
 * ]));
 *
 * </code>
 */
class Date extends ValidatorBase {

	public function validate($validator, $attribute)
	{
		$required = $this->getOption([ 'key' => 'required', 'default' => false ]);
		$presence_of = $this->getOption(['key' => 'message.presence_of', 'default' => 'presence_of']);

		#region get attribute to value
		$year_attribute = $this->getOption('year');
		$month_attribute = $this->getOption('month');
		$day_attribute = $this->getOption('day');

		$year = $validator->getValue($year_attribute);
		$month = $validator->getValue($month_attribute);
		$day = $validator->getValue($day_attribute);
		#end region

		if (is_null($year)) throw new \Exception("year (attribute : $year_attribute) is null.");
		if (is_null($month)) throw new \Exception("month (attribute : $month_attribute) is null.");
		if (is_null($day)) throw new \Exception("day (attribute : $day_attribute) is null.");

		if ($required) {
			if ($year === '' && $month === '' && $day === '') {
				// デフォルトはpresence_of 'message.presence_of'オプションでカスタマイズできる
				$this->appendMessageSimply($validator, $attribute, $presence_of);
				return false;
			}
		} else {
			// 必須じゃないかつ、全て空だったらスルーする
			if ($year === '' && $month === '' && $day === '') {
				return true;
			}
		}

		// checkdateに空文字を渡すとWarningが出る
		if ($year === '' || $month === '' || $day === ''
			|| ! checkdate($month, $day, $year)) {
			$this->appendMessageSimply($validator, $attribute, "invalid_date");
			return false;
		}
		return true;
	}
}

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
	 * ラジオボタンの検証を設定するショートハンド
	 * @param string $attribute
	 * @param string $label
	 * @param array $options
	 * @param bool $required
	 * @return self $this
	 */
	public function chooseOne($attribute, $label, $options, $required = false)
	{
		if ($required) {
			$this->add($attribute, new PresenceOf([
				'cancelOnFail' => true,
			]));
		}
		$this->add($attribute, new InclusionIn([
			'message' => $this->translate->query('err.choose_one'),
			'domain' => $options,
		]));
		$this->setLabel($attribute, $label);
		return $this;
	}

	/**
	 * 日付フィールドの検証を設定するショートハンド
	 * @param array $attributes [date, year, month, day]
	 * @param string $label
	 * @param bool $required
	 * @return self $this
	 */
	public function dateField($attributes, $label, $required) {
		// $attributes['date'] はラベルとメッセージを設定するためだけに使用
		// 中身は見てません
		$this->add($attributes['date'], new Date([
			'required' => $required,
			'year' => $attributes['year'],
			'month' => $attributes['month'],
			'day' => $attributes['day']
		]));
		$this->setLabel($attributes['date'], $label);
		return $this;
	}

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
	 * @param string $filter
	 * @return self $this
	 */
	public function numberField($attribute, $label, $minValue = null, $maxValue = null, $required = false, $filter = 'int')
	{
		$this->setFilters($attribute, [$filter]);
		if ($required) {
			$this->add($attribute, new PresenceOf());
		}
		$options = Ax::zero();
		if (!is_null($minValue)) {
			$options['minimum'] = $minValue;
			$options['message'] = $this->translate->minValue($minValue);
		}
		if (!is_null($maxValue)) {
			$options['maximum'] = $maxValue;
			$options['message'] .= $this->translate->maxValue($maxValue);
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
