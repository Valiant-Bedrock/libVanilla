<?php


namespace sylvrs\vanilla\transaction\network;


use pocketmine\network\mcpe\protocol\AnvilDamagePacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use sylvrs\vanilla\transaction\TransactionManager;
use sylvrs\vanilla\VanillaBase;

class AnvilDamageHandler extends PacketHandler {

	public function __construct(TransactionManager $session) {
		parent::__construct(AnvilDamagePacket::class, $session);
	}

	public function handle(ServerboundPacket $packet): bool {
		if($packet instanceof AnvilDamagePacket) {
			if(!$this->session->hasAnvilTransaction()) {
				// we don't have an anvil transaction, so we shouldn't handle this at all
				return false;
			}
			VanillaBase::getInstance()->getLogger()->debug("Received anvil damage: [position=({$packet->getX()},{$packet->getY()},{$packet->getZ()}),damageAmount={$packet->getDamageAmount()}]");
			return true;
		}
		return false;
	}
}