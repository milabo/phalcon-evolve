<?php

namespace Phalcon\Evolve\View;

use Phalcon\Evolve\View\EventListenerPlugin\IEventListenerPlugin;

class EventListener {
	
	/** @type IEventListenerPlugin[] */
	protected $beforeRenderPlugins = [];
	/** @type IEventListenerPlugin[] */
	protected $beforeRenderViewPlugins = [];
	/** @type IEventListenerPlugin[] */
	protected $afterRenderViewPlugins = [];
	/** @type IEventListenerPlugin[] */
	protected $afterRenderPlugins = [];
	/** @type IEventListenerPlugin[] */
	protected $notFoundViewPlugins = [];

	/**
	 * $event->stop() で停止可能
	 * 
	 * @param \Phalcon\Events\Event $event
	 * @param \Phalcon\Mvc\View $view
	 */
	public function beforeRender($event, $view)
	{
		foreach ($this->beforeRenderPlugins as $plugin) {
			$plugin->onEvent($event, $view);
		}
	}

	/**
	 * $event->stop() で停止可能
	 * 
	 * @param \Phalcon\Events\Event $event
	 * @param \Phalcon\Mvc\View $view
	 */
	public function beforeRenderView($event, $view)
	{
		foreach ($this->beforeRenderViewPlugins as $plugin) {
			$plugin->onEvent($event, $view);
		}
	}

	/**
	 * @param \Phalcon\Events\Event $event
	 * @param \Phalcon\Mvc\View $view
	 */
	public function afterRenderView($event, $view)
	{
		foreach ($this->afterRenderViewPlugins as $plugin) {
			$plugin->onEvent($event, $view);
		}
	}

	/**
	 * @param \Phalcon\Events\Event $event
	 * @param \Phalcon\Mvc\View $view
	 */
	public function afterRender($event, $view)
	{
		foreach ($this->afterRenderPlugins as $plugin) {
			$plugin->onEvent($event, $view);
		}
	}

	/**
	 * @param \Phalcon\Events\Event $event
	 * @param \Phalcon\Mvc\View $view
	 */
	public function notFoundView($event, $view)
	{
		foreach ($this->notFoundViewPlugins as $plugin) {
			$plugin->onEvent($event, $view);
		}
	}
	
	public function addBeforeRenderPlugin(IEventListenerPlugin $plugin)
	{
		$this->beforeRenderPlugins[] = $plugin;
	}
	
	public function addBeforeRenderViewPlugin(IEventListenerPlugin $plugin)
	{
		$this->beforeRenderViewPlugins[] = $plugin;
	}
	
	public function addAfterRenderViewPlugin(IEventListenerPlugin $plugin)
	{
		$this->afterRenderViewPlugins[] = $plugin;
	}
	
	public function addAfterRenderPlugin(IEventListenerPlugin $plugin)
	{
		$this->afterRenderPlugins[] = $plugin;
	}
	
	public function addNotFoundViewPlugin(IEventListenerPlugin $plugin)
	{
		$this->notFoundViewPlugins[] = $plugin;
	}
	
}