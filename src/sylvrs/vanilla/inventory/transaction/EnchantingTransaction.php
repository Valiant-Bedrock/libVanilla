<?php


namespace sylvrs\vanilla\inventory\transaction;


use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\item\Item;
use sylvrs\vanilla\inventory\action\EnchantingAction;
use sylvrs\vanilla\inventory\EnchantInventory;

class EnchantingTransaction extends InventoryTransaction {

	/** The cost of the transaction (1 - 3) */
	protected int $cost = 1;

	public function setCost(int $cost): void {
		$this->cost = $cost;
	}

	public function addAction(InventoryAction $action): void {
		if(!$action instanceof EnchantingAction) {
			return;
		}
		parent::addAction($action);
	}

	public function validate() : void{
		$this->squashDuplicateSlotChanges();
		if(count($this->actions) < 3) {
			throw new TransactionValidationException("Transaction must have at least three actions to be executable");
		}
		foreach($this->actions as $action) {
			$action->validate($this->getSource());
		}
	}
	public function onSuccess(EnchantInventory $inventory, Item $item): void {
		$inventory->setItem(EnchantInventory::TARGET, $item);
		if(!$this->source->isCreative()) {
			$this->source->getXpManager()->subtractXpLevels($this->cost);
		}
	}


}