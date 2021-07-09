<?php


namespace sylvrs\vanilla\transaction\network;


use pocketmine\network\mcpe\protocol\FilterTextPacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use sylvrs\vanilla\transaction\TransactionManager;

class FilterTextHandler extends PacketHandler {

	public function __construct(TransactionManager $session) {
		parent::__construct(FilterTextPacket::class, $session);
	}

	public function handle(ServerboundPacket|FilterTextPacket $packet): bool {
		if(!$this->session->hasAnvilTransaction()) {
			return false;
		}
		$this->session->getPlayer()->getNetworkSession()->sendDataPacket(FilterTextPacket::create($packet->getText(), true));
		$this->session->getAnvilTransaction()->setName($packet->getText());
		return true;
	}
}