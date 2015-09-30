<?php

namespace Phalcon\Evolve\View\EventListenerPlugin;


interface IEventListenerPlugin {

	/**
	 * @param \Phalcon\Events\Event $event
	 * @param \Phalcon\Mvc\View $view
	 */
	public function onEvent($event, $view);

} 