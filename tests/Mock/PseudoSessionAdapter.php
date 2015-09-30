<?php
/**
 * Author: sukobuto.com
 * Since: 14/06/16 2:11
 * Copyright: 2014 sukobuto.com All Rights Reserved.
 */

namespace Mock;

use Phalcon\Session\AdapterInterface;

class PseudoSessionAdapter implements AdapterInterface {
	
	protected $id;
	
	private $data = array();
	
	public function start() {
		$this->id = time() . rand(1000,9999);
	}
	
	public function setOptions($options) {}
	
	public function getOptions() {}
	
	public function get($index, $defaultValue=null)
	{
		if (isset($this->data[$index])) return $this->data[$index];
		return $defaultValue;
	}
	
	public function set($index, $value)
	{
		$this->data[$index] = $value;
	}
	
	public function has($index)
	{
		return isset($this->data[$index]);
	}
	
	public function remove($index)
	{
		unset($this->data[$index]);
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	public function isStarted()
	{
		return isset($this->id);
	}
	
	public function destroy($session_id=null)
	{
		if ($session_id === $this->id)
			$this->data = array();
	}

	/**
	 * Regenerate session's id
	 *
	 * @param bool $deleteOldSession
	 * @return AdapterInterface
	 */
	public function regenerateId($deleteOldSession = true)
	{
		// do nothing
	}
}