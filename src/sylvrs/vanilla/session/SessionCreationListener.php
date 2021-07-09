<?php


namespace sylvrs\vanilla\session;


use pocketmine\event\player\PlayerQuitEvent;
use sylvrs\vanilla\VanillaListener;

class SessionCreationListener extends VanillaListener {

	public function handleQuit(PlayerQuitEvent $event): void {
		$this->plugin->getSessionManager()->delete($event->getPlayer());
	}

}