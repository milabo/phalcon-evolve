<?php

namespace Phalcon\Evolve\Cache;

use Phalcon\Cache\Backend;
use Phalcon\Cache\BackendInterface;

class NullBackend extends Backend implements BackendInterface {
	/**
	 * Returns a cached content
	 *
	 * @param int|string $keyName
	 * @param int $lifetime
	 * @return  mixed
	 */
	public function get($keyName, $lifetime = null)
	{
		return null;
	}

	/**
	 * Stores cached content into the file backend and stops the frontend
	 *
	 * @param int|string $keyName
	 * @param string $content
	 * @param long $lifetime
	 * @param boolean $stopBuffer
	 */
	public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = null)
	{
		
	}

	/**
	 * Deletes a value from the cache by its key
	 *
	 * @param int|string $keyName
	 * @return boolean
	 */
	public function delete($keyName)
	{
		return true;
	}

	/**
	 * Query the existing cached keys
	 *
	 * @param string $prefix
	 * @return array
	 */
	public function queryKeys($prefix = null)
	{
		return [];
	}

	/**
	 * Checks if cache exists and it hasn't expired
	 *
	 * @param  string $keyName
	 * @param  long $lifetime
	 * @return boolean
	 */
	public function exists($keyName = null, $lifetime = null)
	{
		return false;
	}

	/**
	 * Immediately invalidates all existing items.
	 *
	 * @return boolean
	 */
	public function flush()
	{
		return true;
	}

} 