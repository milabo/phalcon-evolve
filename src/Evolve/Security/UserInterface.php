<?php

namespace Phalcon\Evolve\Security;


interface UserInterface {

	/**
	 * @return integer
	 */
	public function getId();

	/**
	 * セッションインデクサIDを取得
	 * Redis でリアルタイムセッションへのインデックスを管理するために使用する。
	 *
	 * @return integer
	 */
	public function getSessionIndexerId();

	/**
	 * @return bool
	 */
	public function isEnabled();

	/**
	 * @return UserInterface
	 */
	public function eraseCredentials();

	/**
	 * @return string
	 */
	public function getRole();

	/**
	 * @return string
	 */
	public function serialize();

	/**
	 * @param string $data
	 * @return void
	 */
	public function unserialize($data);

}
