<?php


namespace sylvrs\vanilla\transaction\network;


use pocketmine\block\inventory\EnchantInventory;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\inventory\transaction\TransactionException;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\types\inventory\NetworkInventoryAction;
use pocketmine\network\mcpe\protocol\types\inventory\UIInventorySlotOffset;
use pocketmine\utils\AssumptionFailedError;
use sylvrs\vanilla\inventory\action\AnvilAction;
use sylvrs\vanilla\inventory\action\EnchantingAction;
use sylvrs\vanilla\inventory\AnvilInventory;
use sylvrs\vanilla\inventory\transaction\AnvilTransaction;
use sylvrs\vanilla\inventory\transaction\EnchantingTransaction;
use sylvrs\vanilla\item\enchantment\EnchantmentManager;
use sylvrs\vanilla\transaction\TransactionSession;
use sylvrs\vanilla\VanillaBase;

class InventoryTransactionHandler extends PacketHandler {

	public function __construct(TransactionSession $session) {
		parent::__construct(InventoryTransactionPacket::class, $session);
	}
	public function handle(ServerboundPacket|InventoryTransactionPacket $packet): bool {
		$player = $this->session->getPlayer();
		if($player->getCurrentWindow() === null) {
			return false;
		}
		$isAnvil = false;
		$isEnchanting = false;
		$actions = [];

		foreach($packet->trData->getActions() as $action) {
			if($this->isFromEnchantingTable($action)) {
				$isEnchanting = true;
			} elseif($this->isFromAnvil($action)) {
				$isAnvil = true;
			} else {
				throw new AssumptionFailedError("Only anvils and enchantment tables should be processed");
			}
			if(($action = $this->createInventoryAction($action)) !== null) {
				$actions[] = $action;
			}
		}
		if($isAnvil) {
			$this->handleAnvil($actions);
			return true;
		} elseif($isEnchanting) {
			// TODO: Let's make sure enchanting also handles SlotChangeActions :)
			$this->handleEnchanting($actions);
		}
		return false;
	}

	/**
	 * TODO: Clean this up a little more
	 */
	public function isFromAnvil(NetworkInventoryAction $action): bool {
		return
			($action->sourceType === NetworkInventoryAction::SOURCE_TODO &&
				($action->windowId === NetworkInventoryAction::SOURCE_TYPE_ANVIL_RESULT)) ||
			($this->session->hasAnvilTransaction() && !$action->oldItem->getItemStack()->equals($action->newItem->getItemStack()) &&
				isset(UIInventorySlotOffset::ANVIL[$action->inventorySlot])) || $this->session->getPlayer()->getCurrentWindow() instanceof AnvilInventory;
	}

	/**
	 * @param InventoryAction[] $actions
	 */
	public function handleAnvil(array $actions): void {
		$player = $this->session->getPlayer();
		$logger = VanillaBase::getInstance()->getLogger();

		$anvilTransaction = $this->session->getAnvilTransaction();
		if($anvilTransaction === null) {
			$anvilTransaction = new AnvilTransaction($player, $this->session, $actions);
			$this->session->setAnvilTransaction($anvilTransaction);
		} else {
			foreach($actions as $action) {
				$anvilTransaction->addAction($action);
			}
		}

		$inventoryManager = $player->getNetworkSession()->getInvManager();
		if($inventoryManager === null) {
			$logger->debug("Inventory manager is null");
			$this->session->removeAnvilTransaction();
			return;
		}

		try {
			$anvilTransaction->validate();
		} catch(TransactionValidationException $exception) {
			$logger->info("Validation exception: {$exception->getMessage()}");
			return;
		}

		try {
			$inventoryManager->onTransactionStart($anvilTransaction);
			$anvilTransaction->execute();
		} catch(TransactionValidationException $exception) {
			$logger->debug("Anvil execution exception: {$exception->getMessage()}");
			$this->sync($anvilTransaction, $inventoryManager);
		} finally {
			$logger->debug("Removing anvil transaction after execution");
			$this->session->removeAnvilTransaction();
		}
	}

	public function isFromEnchantingTable(NetworkInventoryAction $action): bool {
		return ($action->sourceType === NetworkInventoryAction::SOURCE_TODO &&
				($action->windowId === EnchantmentManager::SOURCE_TYPE_ENCHANT_MATERIAL ||
					$action->windowId === EnchantmentManager::SOURCE_TYPE_ENCHANT_INPUT ||
					$action->windowId === NetworkInventoryAction::SOURCE_TYPE_ENCHANT_OUTPUT
				)) || ($this->session->hasEnchantingTransaction() && !$action->oldItem->getItemStack()->equals($action->newItem->getItemStack())
				&& isset(UIInventorySlotOffset::ENCHANTING_TABLE[$action->inventorySlot])) || $this->session->getPlayer()->getCurrentWindow() instanceof EnchantInventory;
	}

	/**
	 * @param InventoryAction[] $actions
	 */
	public function handleEnchanting(array $actions): void {
		$player = $this->session->getPlayer();
		$logger = VanillaBase::getInstance()->getLogger();

		$enchantingTransaction = $this->session->getEnchantingTransaction();
		if($enchantingTransaction === null) {
			$enchantingTransaction = new EnchantingTransaction($player, $actions);
			$this->session->setEnchantingTransaction($enchantingTransaction);
		} else {
			foreach($actions as $action) $enchantingTransaction->addAction($action);
		}

		try {
			$enchantingTransaction->validate();
		} catch(TransactionException) {
			// wait until all parts of the transaction are here before complaining
			return;
		}

		$inventoryManager = $player->getNetworkSession()->getInvManager();
		if($inventoryManager === null) {
			$logger->debug("[Session: {$player->getName()}] Inventory manager is null");
			$this->session->removeEnchantingTransaction();
			return;
		}

		try {
			$inventoryManager->onTransactionStart($enchantingTransaction);
			$enchantingTransaction->execute();
		} catch(TransactionException) {
			$this->sync($enchantingTransaction, $inventoryManager);
		} finally {
			// Enchanting is done
			$this->session->removeEnchantingTransaction();
		}
	}

	public function createInventoryAction(NetworkInventoryAction $action): ?InventoryAction {
		$player = $this->session->getPlayer();
		switch($action->sourceType) {
			case NetworkInventoryAction::SOURCE_CONTAINER:
				$invManager = $player->getNetworkSession()->getInvManager();
				if($invManager === null) {
					return null;
				}
				return TypeConverter::getInstance()->createInventoryAction($action, $player, $invManager);
			case NetworkInventoryAction::SOURCE_TODO:
				$oldItem = TypeConverter::getInstance()->netItemStackToCore($action->oldItem->getItemStack());
				$newItem = TypeConverter::getInstance()->netItemStackToCore($action->newItem->getItemStack());

				$slot = UIInventorySlotOffset::ENCHANTING_TABLE[$action->inventorySlot] ??
					UIInventorySlotOffset::ANVIL[$action->inventorySlot] ??
					$action->inventorySlot;

				$currentInventory = $player->getCurrentWindow();
				return match ($action->windowId) {
					EnchantmentManager::SOURCE_TYPE_ENCHANT_INPUT,
					EnchantmentManager::SOURCE_TYPE_ENCHANT_MATERIAL,
					NetworkInventoryAction::SOURCE_TYPE_ENCHANT_OUTPUT =>
						new EnchantingAction($currentInventory, $slot, $oldItem, $newItem, $action->windowId),
					NetworkInventoryAction::SOURCE_TYPE_ANVIL_RESULT =>
						$currentInventory instanceof AnvilInventory ? new AnvilAction($currentInventory, $slot, $oldItem, $newItem, $action->windowId) : null,
					default => null
				};
		}
		return null;
	}

	public function sync(InventoryTransaction $transaction, InventoryManager $manager): void {
		$manager->syncAll();
	}
}