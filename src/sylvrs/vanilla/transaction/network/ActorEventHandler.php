<?php


namespace sylvrs\vanilla\transaction\network;


use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use sylvrs\vanilla\transaction\TransactionSession;
use sylvrs\vanilla\VanillaBase;

class ActorEventHandler extends PacketHandler {

	public function __construct(TransactionSession $session) {
		parent::__construct(ActorEventPacket::class, $session);
	}

	public function handle(ServerboundPacket|ActorEventPacket $packet): bool {
		if($packet->event === ActorEventPacket::PLAYER_ADD_XP_LEVELS) {
			if(!$this->session->hasOpenTransaction()) {
				return false;
			}
			if($this->session->hasAnvilTransaction()) {
				$serverCost = $this->session->getAnvilTransaction()->getCost();
				$clientCost = abs($packet->data);
				if($serverCost !== -1 && $serverCost !== $clientCost) {
					VanillaBase::getInstance()->getLogger()->debug("Discrepancy in anvil transaction costs: [Server => $serverCost, Client => $clientCost]");
				}
			} else {
				// we don't really have to do anything right now
			}
			return true;
		}
		return false;
	}
}