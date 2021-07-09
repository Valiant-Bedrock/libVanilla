<?php


namespace sylvrs\vanilla\transaction;


use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use sylvrs\vanilla\inventory\EnchantInventory;
use sylvrs\vanilla\VanillaBase;

class TransactionListener implements Listener {

	public function __construct(protected VanillaBase $plugin) {}

	public function handleQuit(PlayerQuitEvent $event): void {
		$this->plugin->getTransactionManager()->delete($event->getPlayer());
	}

	public function handleDataPacketReceive(DataPacketReceiveEvent $event): void {
		// check if we have a valid network session & connected player before deciding to listen to packets
		if(!($networkSession = $event->getOrigin())->isConnected()) {
			return;
		}
		if(!($player = $networkSession->getPlayer()) instanceof Player || !$player->isOnline()) {
			return;
		}
		// get or create a transaction session to attach to the player
		$session = $this->plugin->getTransactionManager()->get($player);

		if($session->shouldHandle()) {
			$packet = $event->getPacket();
			if($session->hasHandler($packet)) {
				$handler = $session->getHandler($packet);
				// $player->getLogger()->debug("Attempting to handle {$packet->getName()}");

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