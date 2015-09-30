<?php

namespace Phalcon\Evolve\Logger;

use Phalcon\Logger\AdapterInterface;
use Phalcon\Logger\Adapter;

class NullLogger extends Adapter implements AdapterInterface {

	public function getFormatter()
	{
		return null;
	}

	public function close()
	{
		return true;
	}
	
	protected function logInternal($message, $type, $time, $context) {
		// do nothing
	}

}