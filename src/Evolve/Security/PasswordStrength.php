<?php
/**
 * Author: Kenta Suzuki
 * Since: 2014/12/01 14:43
 * Copyright: 2014 sukobuto.com All Rights Reserved.
 */

namespace Phalcon\Evolve\Security;

use Phalcon\Evolve\PrimitiveExtension\StringExtension as Sx;

/**
 * パスワード強度を検証する
 * @package Phalcon\Evolve\Security
 */
class PasswordStrength {
	
	const
		CLASS_SMALL_CASE = '/[a-z]/',
		CLASS_LARGE_CASE = '/[A-Z]/',
		CLASS_NUMBER = '/[0-9]/',
		CLASS_SYMBOL = '/[!-\/:-@≠\[-`{-~]/i';
	
	/** @type string[] 検証する文字クラス定義 */
	protected $classes;
	/** @type integer 必要とする文字クラス数 */
	protected $class_num;
	/** @type integer 最低文字数 */
	protected $min_length;

	/**
	 * @param $class_num
	 * @param $min_length
	 * @param array $classes
	 */
	public function __construct($min_length, $class_num, $classes = null)
	{
		$this->min_length = $min_length;
		$this->class_num = $class_num;
		$this->classes = isset($classes)
			? $classes
			: [
				self::CLASS_SMALL_CASE,
				self::CLASS_LARGE_CASE,
				self::CLASS_NUMBER,
				self::CLASS_SYMBOL,
			];
	}

	/**
	 * @param string $password
	 * @return bool
	 * @throws PasswordNotEnoughLengthException
	 * @throws PasswordNotEnoughClassesException
	 */
	public function validate($password)
	{
		if (Sx::x($password)->length() < $this->min_length)
			throw new PasswordNotEnoughLengthException();
		$matches = 0;
		foreach ($this->classes as $class) {
			if (preg_match($class, $password)) $matches++;
		} 
		if ($matches < $this->class_num)
			throw new PasswordNotEnoughClassesException();
		return true;
	}

}

class PasswordNotEnoughLengthException extends \Exception {}
class PasswordNotEnoughClassesException extends \Exception {}