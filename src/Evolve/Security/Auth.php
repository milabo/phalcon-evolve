<?php

namespace Phalcon\Evolve\Security;

use Phalcon\DI\Injectable;
use Phalcon\DiInterface;
use Phalcon\Evolve\PrimitiveExtension\ArrayExtension as Ax;

/**
 * 認証クラス
 * セッションとは別にユーザごとの認証情報を Redis 上に保持し、
 * 各セッションに参照情報を記録することで複数セッション使用時の更新漏れを防ぐ。
 * また session_key を変更することで異なる種類の認証情報を同じセッションで維持できる。
 * @package Phalcon\Evolve\Security
 * @property \Phalcon\Evolve\System\Clock $clock
 * @property \Phalcon\Config $config
 * @property \Phalcon\Logger\AdapterInterface $logger
 */
class Auth extends Injectable {

	/** 認証情報を保存するためのキープレフィックス */
	const SESSION_AUTH_PREFIX = 'auth:';
	/** セッション上に認証情報への参照(indexer_id)を保存するためのデフォルトキー */
	const DEFAULT_SESSION_KEY = '_auth';
	/** ユーザあたりの最大セッション数 */
	const USER_MAX_SESSIONS = 5;
	
	/** @var UserInterface; */
	protected $user;
	/** @var string */
	protected $user_class;
	/** @type string */
	protected $session_key = self::DEFAULT_SESSION_KEY;
	/** @type string */
	protected $session_surrogate_id;
	
	public static function load($session_key = self::DEFAULT_SESSION_KEY, $indexer_id = null)
	{
		return (new self())->setSessionKey($session_key)->_load($indexer_id);
	}

	public static function factoryForMaintenance($session_key = self::DEFAULT_SESSION_KEY)
	{
		return (new self())->setSessionKey($session_key);
	}

	protected function setSessionKey($session_key)
	{
		$this->session_key = $session_key;
		return $this;
	}

	/**
	 * @param bool $shared
	 * @return \Redis
	 */
	protected function getRedis($shared = false)
	{
		if ($shared) $this->di->getShared('redis');
		return $this->di->get('redis');
	}

	/**
	 * @param string $indexer_id
	 * @return self $this
	 */
	protected function _load($indexer_id = null)
	{
		if (is_null($indexer_id) and $this->session->has($this->session_key)) {
			$indexer_id = $this->session->get($this->session_key);
		}
		$data = $this->getRedis(true)
			->hGetAll(self::SESSION_AUTH_PREFIX . $indexer_id);
		// 逆引きして sessions:<indexer_id> に含まれていないセッションIDなら弾く
		if ($data && false !== $this->getRedis(true)->zScore("sessions:$indexer_id", $this->session->getId())) {
			$this->user_class = $data['class'];
			$this->user = new $this->user_class();
			$this->user->unserialize($data['object']);
		}
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isAuthenticated()
	{
		return isset($this->user);
	}

	/**
	 * @param string $role
	 * @return bool
	 */
	public function isAuthenticatedAs($role)
	{
		return isset($this->user) and $this->user->getRole() === $role;
	}

	/**
	 * セッションに認証情報を登録する
	 * デフォルトでセッションIDを再生成するため、前後でセッションIDが変動することに注意してください
	 * @param UserInterface $user
	 * @param bool $regenerate_session_id セッションIDを再生成するか否か
	 */
	public function register(UserInterface $user, $regenerate_session_id = true)
	{
		$user = $user->eraseCredentials();
		$this->user = $user;
		$this->user_class = get_class($user);
		$id = $user->getSessionIndexerId();
		if ($regenerate_session_id) {
			session_regenerate_id(true);
			$this->disposeSessionSurrogateId();
		}
		$this->session->set($this->session_key, $id);
		$redis = $this->getRedis(true);
		$redis->hMset(
			self::SESSION_AUTH_PREFIX . $id,
			array(
				'class' => get_class($user),
				'object' => $user->serialize()
			)
		);
		// Redis Sorted Set を使って多重ログイン数を制御
		$session_id = $this->session->getId();
		$user_sessions_key = "sessions:$id";
		$redis->zadd($user_sessions_key, $this->clock->nowTs() ,$session_id);
		$dropped = $redis->zRange($user_sessions_key, 0, -self::USER_MAX_SESSIONS - 1);
		if (!empty($dropped)) {
			/** @var \Redis $session_redis */
			$session_redis = $this->di->get('redis');
			$session_redis->select($this->config['redis']['db_session']);
			$session_redis->setOption(\Redis::OPT_PREFIX, '');
			foreach ($dropped as $saved_id) {
				$this->logger->info("destroy session: $saved_id");
				$redis->zRem($user_sessions_key, $saved_id);
				$session_redis->del("PHPREDIS_SESSION:$saved_id");
			}
		}
	}

	public function update(UserInterface $user)
	{
		$user = $user->eraseCredentials();
		$this->user = $user;
		$id = $user->getSessionIndexerId();
		$this->getRedis(true)->hMset(
			self::SESSION_AUTH_PREFIX . $id,
			array(
				'class' => get_class($user),
				'object' => $user->serialize()
			)
		);
	}

	/**
	 * @return UserInterface
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * 認証情報をクリア
	 * デフォルトでセッションIDを再生成するため、前後でセッションIDが変動することに注意してください
	 * @param bool $regenerate_session_id セッションIDを再生成するか否か
	 * @return self $this
	 */
	public function clear($regenerate_session_id = true)
	{
		$this->session->remove($this->session_key);
		if ($regenerate_session_id) {
			session_regenerate_id(true);
			$this->disposeSessionSurrogateId();
		}
		$this->user = null;
		return $this;
	}

	/**
	 * 対象ユーザの認証およびセッションを強制的に破棄
	 * @param UserInterface $user
	 */
	public function disposeFor(UserInterface $user)
	{
		$redis = $this->getRedis(true);
		$id = $user->getSessionIndexerId();
		// 認証情報を削除
		$redis->del(self::SESSION_AUTH_PREFIX . $id);
		// すべてのセッションを削除
		$user_sessions_key = "sessions:$id";
		$sessions = $redis->zRange($user_sessions_key, 0, -1);
		$redis->del($user_sessions_key);
		if (!empty($sessions)) {
			$session_redis = $this->getRedis();
			$session_redis->select($this->config['redis']['db_session']);
			$session_redis->setOption(\Redis::OPT_PREFIX, '');
			foreach ($sessions as $saved_id) {
				$this->logger->info("destroy session: $saved_id");
				$session_redis->del("PHPREDIS_SESSION:$saved_id");
			}
		}
	}

	/**
	 * ドメイン間でセッションの橋渡しをするための代理IDを生成する
	 * @return string
	 */
	public function getSessionSurrogateId()
	{
		if (isset($this->session_surrogate_id)) return $this->session_surrogate_id;
		$session_id = $this->session->getId();
		$this->session_surrogate_id = hash('sha256', $session_id . Ax::x($_SERVER)->getOrElse('HTTP_USER_AGENT', "NO-UA"));
		$this->getRedis(true)->setex("session-surrogate:{$this->session_surrogate_id}", 3600, $session_id);
		return $this->session_surrogate_id;
	}

	/**
	 * ドメイン間でセッションの橋渡しをするための代理IDを破棄する
	 */
	public function disposeSessionSurrogateId()
	{
		if (!$this->session_surrogate_id) return;
		$redis = $this->getRedis(true);
		$redis->del("session-surrogate:{$this->session_surrogate_id}");
		$this->session_surrogate_id = null;
	}

	/**
	 * ドメイン間でセッションの橋渡しをするための代理IDからセッションIDを取得する
	 * @param DiInterface $di
	 * @return string|null
	 */
	public static function getSessionIdFromSurrogateId(DiInterface $di)
	{
		if (isset($_GET['_surrogate'])) {
			$session_surrogate_id = $_GET['_surrogate'];
			/** @var \Redis $redis */
			$redis = $di->getShared('redis');
			$session_id = $redis->get("session-surrogate:$session_surrogate_id");
			if ($session_id && $session_surrogate_id === hash('sha256', $session_id . Ax::x($_SERVER)->getOrElse('HTTP_USER_AGENT', "NO-UA"))) {
				return $session_id;
			}
		}
		return null;
	}
}