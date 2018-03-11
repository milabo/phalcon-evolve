<?php

namespace Phalcon\Evolve\Security;

class EmailAddress {

	public static function isValid($email)
	{
		$email = preg_replace_callback('/.+@(docomo|ezweb)\.ne\.jp$/i', function($matches) {
			$patterns = array('/\.{2,}/', '/\.@/');
			$replacements = array('.', '@');
			return preg_replace($patterns, $replacements, $matches[0]);
		}, $email);
		return !!filter_var($email, FILTER_VALIDATE_EMAIL);
	}

}