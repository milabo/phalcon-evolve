<?php

namespace Phalcon\Evolve\Security;

class UserNotFoundException extends \Exception {
	protected $identifier;

	public function __construct($identifier = null, $message = null, $code = null)
	{
		parent::__construct($message, $code);
		$this->identifier = $identifier;
	}

	/**
	 * @return string Identifier ユーザの検索に用いた識別子
	 */
	public function getIdentifier()
	{
		return $this->identifier;
	}
}