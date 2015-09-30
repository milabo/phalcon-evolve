<?php

namespace Phalcon\Evolve\View\EventListenerPlugin;

/**
 * Class HalfKanaPlugin
 * 全角カナを半角カナに変換する
 * 
 * @package Phalcon\Evolve\View\EventListenerPlugin
 */
class HalfKanaPlugin implements IEventListenerPlugin {
	
	public function onEvent($event, $view)
	{
		$view
			->setContent(
				mb_convert_kana($view->getContent(), 'k', 'utf8')
			);
	}

} 