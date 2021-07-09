<?php


namespace sylvrs\vanilla\inventory\action;


use JetBrains\PhpStorm\Pure;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\inventory\NetworkInventoryAction;
use pocketmine\player\Player;
use sylvrs\vanilla\inventory\EnchantInventory;
use sylvrs\vanilla\item\enchantment\EnchantmentManager;
use sylvrs\vanilla\VanillaBase;

class EnchantingAction extends InventoryAction {

	#[Pure]
	public function __construct(protected EnchantInventory $inventory, protected int $inventorySlot, Item $sourceItem, Item $targetItem, protected int $type) {
		parent::__construct($sourceItem, $targetItem);
	}

	public function getType(): int {
		return $this->type;
	}

	public function validate(Player $source): void {
		$session = VanillaBase::getInstance()->getSessionManager()->get($source);
		$transactionManager = $session->getTransactionManager();
		if(!$transactionManager->hasEnchantingTransaction()) {
			throw new TransactionValidationException("Player doesn't have an existing enchanting transaction");
		}
		if(!$this->inventory->slotExists($this->inventorySlot)){
			throw new TransactionValidationException("Slot does not exist");
		}
		if($this->sourceItem->equals($this->inventory->getTarget(), false, false) || $this->isBook($this->getTargetItem())) {
			foreach($this->targetItem->getEnchantments() as $enchantment) {
				if($enchantment->getLevel() > $enchantment->getType()->getMaxLevel()) {
					throw new TransactionValidationException("Enchantment level exceeds its max level");
				}

			}
		}
		if($this->getType() === EnchantmentManager::SOURCE_TYPE_ENCHANT_MATERIAL) {
			$cost = max(1, abs($this->getSourceItem()->getCount() - 3));
			if($cost > $source->getXpManager()->getXpLevel()) {
				throw new TransactionValidationException("Player XP Level is lower than cost");
			}
			$transaction = $transactionManager->getEnchantingTransaction();
			$transaction->setCost($cost);
		}
	}

	public function onPreExecute(Player $source): bool {
		foreach($this->sourceItem->getEnchantments() as $enchantment) {
			if(EnchantmentManager::isBlacklisted($enchantment->getType())) {
				VanillaBase::getInstance()->getLogger()->debug("Player tried to use blacklisted enchantment: {$enchantment->getType()->getName()}");
				return false;
			}
		}
		return true;
	}

	public function isBook(Item $targetItem): bool {
		return $targetItem->equals(VanillaItems::BOOK()) || $targetItem->getId() === ItemIds::ENCHANTED_BOOK; // no VanillaItems::ENCHANTED_BOOK() :(
	}

	/**
	 * Adds this action's target inventory to the transaction's inventory list.
	 */
	public function onAddToTransaction(InventoryTransaction $transaction) : void{
		$transaction->addInventory($this->inventory);
	}

	/**
	 * Sets the item into the target inventory.
	 */
	public function execute(Player $source) : void {
		$session = VanillaBase::getInstance()->getSessionManager()->get($source);
		if(!$this->inventory instanceof EnchantInventory) {
			VanillaBase::getInstance()->getLogger()->debug("Inventory not instanceof EnchantInventory");
			return;
		}

		$transaction = $session->getTransactionManager()->getEnchantingTransaction();
		switch($this->getType()) {
			case EnchantmentManager::SOURCE_TYPE_ENCHANT_INPUT:
				// :thonkies:
				break;
			case EnchantmentManager::SOURCE_TYPE_ENCHANT_MATERIAL:
				$this->inventory->updateMaterial();
				break;
			case NetworkInventoryAction::SOURCE_TYPE_ENCHANT_OUTPUT:
				$transaction->onSuccess($this->inventory, $this->getSourceItem());
				break;
		}
	}

	public function __toString(): string {
		return "EnchantmentAction(type=$this->type,inventorySlot=$this->inventorySlot,sourceItem={$this->sourceItem->__toString()},targetItem={$this->targetItem->__toString()}";
	}
}