<?php


namespace sylvrs\vanilla\inventory\action;


use JetBrains\PhpStorm\Pure;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\inventory\transaction\TransactionCancelledException;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\types\inventory\NetworkInventoryAction;
use pocketmine\player\Player;
use sylvrs\vanilla\inventory\AnvilInventory;
use sylvrs\vanilla\item\enchantment\EnchantmentManager;
use sylvrs\vanilla\VanillaBase;

class AnvilAction extends InventoryAction {

	#[Pure]
	public function __construct(protected AnvilInventory $inventory, protected int $inventorySlot, Item $sourceItem, Item $targetItem, protected int $type) {
		parent::__construct($sourceItem, $targetItem);
	}

	public function getType(): int {
		return $this->type;
	}

	public function validate(Player $source): void {
		$session = VanillaBase::getInstance()->getSessionManager()->get($source);
		$transactionManger = $session->getTransactionManager();

		if(!$transactionManger->hasAnvilTransaction()) {
			throw new TransactionValidationException("Player doesn't have an existing enchanting transaction");
		}
		if(!$this->inventory->slotExists($this->inventorySlot)){
			throw new TransactionValidationException("Slot ($this->inventorySlot) does not exist");
		}
		switch($this->getType()) {
			case NetworkInventoryAction::SOURCE_TYPE_ANVIL_RESULT:
				$transactionManger->getAnvilTransaction()->setResult($this->sourceItem);
				break;
		}
	}

	public function execute(Player $source): void {
		$session = VanillaBase::getInstance()->getSessionManager()->get($source);
		$transactionManger = $session->getTransactionManager();
		if(!$transactionManger->hasAnvilTransaction()) {
			throw new TransactionCancelledException("Player doesn't have an existing enchanting transaction");
		}

		switch($this->getType()) {
			case NetworkInventoryAction::SOURCE_TYPE_ANVIL_RESULT:
				$transactionManger->getAnvilTransaction()->onSuccess($this->inventory);
				return;
		}
	}

	public function __toString(): string {
		$typeString = match($this->type) {
			EnchantmentManager::SOURCE_TYPE_ANVIL_INPUT => "Anvil Input",
			EnchantmentManager::SOURCE_TYPE_ANVIL_MATERIAL => "Anvil Material",
			NetworkInventoryAction::SOURCE_TYPE_ANVIL_RESULT => "Anvil Result",
			NetworkInventoryAction::SOURCE_TYPE_ANVIL_OUTPUT => "Anvil Output",
			default => "Unknown($this->type)"
		};
		return "AnvilAction(type=$typeString,inventorySlot=$this->inventorySlot,sourceItem={$this->sourceItem->__toString()},targetItem={$this->targetItem->__toString()}";
	}
}