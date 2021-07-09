<?php


namespace sylvrs\vanilla\transaction;


use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use sylvrs\vanilla\inventory\EnchantInventory;
use sylvrs\vanilla\listener\VanillaListener;

class TransactionListener extends VanillaListener {

	public function handleDataPacketReceive(DataPacketReceiveEvent $event): void {
		// check if we have a valid network session & connected player before deciding to listen to packets
		if(!($networkSession = $event->getOrigin())->isConnected()) {
			return;
		}
		if(!($player = $networkSession->getPlayer()) instanceof Player || !$player->isOnline()) {
			return;
		}
		// get the player session
		$session = $this->plugin->getSessionManager()->get($player);
		$transactionManager = $session->getTransactionManager();

		if($transactionManager->shouldHandle()) {
			$packet = $event->getPacket();
			if($transactionManager->hasHandler($packet)) {
				$handler = $transactionManager->getHandler($packet);
				if($handler->handle($packet)) {
					$event->cancel();
				}
			}
		}
	}

	public function handleInventoryTransaction(InventoryTransactionEvent $event): void {
		foreach($event->getTransaction()->getInventories() as $inventory) {
			if($inventory instanceof EnchantInventory) {
				foreach($event->getTransaction()->getActions() as $action) {
					if($action instanceof SlotChangeAction && $action->getTargetItem()->equals(VanillaItems::LAPIS_LAZULI(), true, false)) {
						$event->cancel();
					}
				}
			}
		}
	}

}