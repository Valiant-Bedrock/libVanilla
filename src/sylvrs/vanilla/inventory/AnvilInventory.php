<?php


namespace sylvrs\vanilla\inventory;


use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\world\Position;
use sylvrs\vanilla\block\Anvil;
use sylvrs\vanilla\VanillaBase;

class AnvilInventory extends \pocketmine\block\inventory\AnvilInventory implements HandleableInventory {

	/** @var int */
	public const TARGET = 0;
	/** @var int */
	public const SACRIFICE = 1;

	public function __construct(Position $holder) {
		parent::__construct($holder);
	}

	public function onClose(Player $who): void {
		parent::onClose($who);
		$session = VanillaBase::getInstance()->getSessionManager()->get($who);
		$session->getTransactionManager()->removeAnvilTransaction();
	}

	public function getTarget(): Item {
		return $this->getItem(self::TARGET);
	}

	public function setTarget(Item $item): void {
		$this->setItem(self::TARGET, $item);
	}

	public function getSacrifice(): Item {
		return $this->getItem(self::SACRIFICE);
	}

	public function setSacrifice(Item $item): void {
		$this->setItem(self::SACRIFICE, $item);
	}

	public function onSuccess(Player $player): void {
		$anvil = $player->getWorld()->getBlock($this->getHolder());
		if($anvil instanceof Anvil) {
			$anvil->use();
		}
	}
}