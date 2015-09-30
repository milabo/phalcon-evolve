<?php

use Phalcon\DI,
	Phalcon\Test\UnitTestCase as PhalconTestCase;
use Phalcon\Evolve\Http\ClientPlatform;
use Phalcon\Evolve\Logger\NullLogger;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\DI\FactoryDefault;
use Phalcon\Logger;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;

class UnitTestCase extends PhalconTestCase {

	/** @var bool */
	private $_loaded = false;
	
	protected $temp_dir;
	
	public function setUp(Phalcon\DiInterface $di = null, Phalcon\Config $config = null)
	{
		$this->temp_dir = __DIR__ . '/temp';
#region $di にサービスを設定
		$di = new FactoryDefault();
		DI::reset();

		$di->set('logger', function() {
			return new NullLogger();
		}, true);
		
		$di->set('clock', function() {
			return new \Phalcon\Evolve\System\ClockForTest();
		}, true);

		/**
		 * The Asset Manager component
		 */
		$di->set('assets', function () {
			return new Phalcon\Assets\Manager();
		}, true);

		/**
		 * The URL component is used to generate all kind of urls in the application
		 */
		$di->set('url', function () {
			$url = new UrlResolver();
			return $url;
		}, true);

		$di->set('client', function () {
			return new ClientPlatform();
		}, true);

		/**
		 * Start the session the first time some component request the session service
		 */
		$di->set('session', function() use ($config) {
			$session = new Mock\PseudoSessionAdapter();
			$session->start();
			return $session;
		}, true);

		$di->set('crypt', function() {
			$crypt = new Phalcon\Crypt();
			$crypt->setKey('asdfetyuife86kaejiue486a8jdscas');
			return $crypt;
		}, true);

		/**
		 * Redis connection is created based in the parameters defined in the configuration file
		 */
		$di->set('redis', function() use($config) {
			$redis = new Redis();
			$redis->connect($config->redis->host, $config->redis->port);
			$redis->setOption(Redis::OPT_PREFIX, 'test:');
			return $redis;
		});

		/**
		 * Register the flash service with the Twitter Bootstrap classes
		 */
		$di->set('flash', function() {
			return new Phalcon\Flash\Direct(array(
				'error' => 'alert alert-error',
				'success' => 'alert alert-success',
				'notice' => 'alert alert-info',
			));
		}, true);

#endregion

		DI::setDefault($di);
		
		parent::setUp($di, $config);
		$this->_loaded = true;
	}
	
	public function __destruct()
	{
		if (!$this->_loaded) {
			throw new \PHPUnit_Framework_IncompleteTestError('Please run parent::setUp().');
		}
	}
} 