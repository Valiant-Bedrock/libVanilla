<?php


namespace sylvrs\vanilla\inventory;


use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use sylvrs\vanilla\VanillaBase;

class EnchantInventory extends \pocketmine\block\inventory\EnchantInventory implements HandleableInventory  {

	public const TARGET = 0;
	public const MATERIAL = 1;

	public function onOpen(Player $who): void {
		parent::onOpen($who);
		$this->updateMaterial();
	}

	public function onClose(Player $who): void {
		$this->setMaterial(ItemFactory::air());
		parent::onClose($who);
		$session = VanillaBase::getInstance()->getSessionManager()->get($who);
		$session->getTransactionManager()->removeEnchantingTransaction();
	}

	public function getTarget(): Item {
		return $this->getItem(self::TARGET);
	}

	public function setTarget(Item $item): void {
		$this->setItem(self::TARGET, $item);
	}

	public function getMaterial(): Item {
		return $this->getItem(self::MATERIAL);
	}


	public function setMaterial(Item $item): void {
		$this->setItem(self::MATERIAL, $item);
	}

	public function updateMaterial(): void {
		$this->setMaterial(VanillaItems::LAPIS_LAZULI()->setCount(3));
	}

}