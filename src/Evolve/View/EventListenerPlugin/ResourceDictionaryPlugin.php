<?php

namespace Phalcon\Evolve\View\EventListenerPlugin;


class ResourceDictionaryPlugin implements IEventListenerPlugin {

	/** @type string リソースディクショナリPHPファイルパス */
	protected $path;
	/** @type \Phalcon\Config リソースディクショナリ内で参照する設定 */
	protected $config;
	/** @type string View 内変数定義名 */
	protected $var_name;

	/**
	 * @param string $path
	 * @param \Phalcon\Config $config
	 * @param string $var_name
	 */
	public function __construct($path, $config, $var_name = 'rd')
	{
		$this->path = $path;
		$this->config = $config;
		$this->var_name = $var_name;
	}
	
	public function onEvent($event, $view)
	{
		$config = $this->config; // リソースディクショナリPHPファイル内で使うため変数定義
		$view->setVar($this->var_name, include $this->path);
	}

} 