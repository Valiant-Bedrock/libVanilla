<?php


namespace sylvrs\vanilla\inventory\transaction;


use JetBrains\PhpStorm\Pure;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\item\Armor;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\item\TieredTool;
use pocketmine\item\ToolTier;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use sylvrs\vanilla\inventory\AnvilInventory;
use sylvrs\vanilla\item\enchantment\IncompatibleEnchantMap;
use sylvrs\vanilla\transaction\TransactionSession;

class AnvilTransaction extends InventoryTransaction {

	/** @var string */
	public const COST_TAG = "RepairCost";
	/** @var string */
	public const USES_TAG = "Uses";

	protected string $name = "";
	protected int $cost = -1;

	protected ?Item $target = null;
	protected ?Item $sacrifice = null;

	protected ?Item $result = null;

	public function __construct(Player $source, protected TransactionSession $session, array $actions = []) {
		parent::__construct($source, $actions);
	}

	public function getName(): string {
		return $this->name;
	}

	public function setName(string $name): void {
		$this->name = $name;
	}

	public function getCost(): int {
		return $this->cost;
	}

	public function setTarget(?Item $target): void {
		$this->target = $target;
	}

	public function hasTarget(): bool {
		return $this->target instanceof Item && !$this->target->isNull();
	}

	public function setSacrifice(?Item $sacrifice): void {
		$this->sacrifice = $sacrifice;
	}

	public function getResult(): ?Item {
		return $this->result;
	}

	public function setResult(?Item $result): void {
		$this->result = $result;
	}

	public function hasResult(): bool {
		return $this->result instanceof Item && !$this->result->isNull();
	}

	public function validate(): void {
		$this->squashDuplicateSlotChanges();

		if(count($this->actions) < 3) {
			throw new TransactionValidationException("Transaction must have at least three actions to be executable");
		}

		$haveItems = [];
		$needItems = [];
		$this->matchItems($needItems, $haveItems);
		$this->updateItems();
		if(!$this->hasTarget()) {
			throw new TransactionValidationException("Missing target item for transaction");
		}
		$this->checkResult();
	}

	public function updateItems(): void {
		foreach($this->actions as $action) {
			if($action instanceof SlotChangeAction && $action->getInventory() instanceof AnvilInventory && !$action->getTargetItem()->isNull()) {
				switch($action->getSlot()) {
					case AnvilInventory::TARGET:
						$this->setTarget($action->getTargetItem());
						break;
					case AnvilInventory::SACRIFICE:
						$this->setSacrifice($action->getTargetItem());
						break;
					default:
						// uh, oh. this shouldn't happen
						throw new TransactionValidationException("Invalid slot ({$action->getSlot()}) supplied to anvil transaction");
				}
			}
		}
	}

	/**
	 * Anvil Calculations:
	 * RepairCost = (2^x - 1, where x is Uses)
	 * Uses = sqrt(RepairCost + 1)
	 * If player is in survival, max cost of an anvil is 39 levels,
	 * otherwise, it's not capped
	 *
	 * Renaming *always* costs one level
	 */
	public function calculateTransactionCost(Item $target, ?Item $sacrifice = null): int {
		$targetUses = $this->getUses($target);
		$cost = (2 ** $targetUses) - 1;
		if($sacrifice !== null) {
			if($target instanceof Durable) {
				if($sacrifice instanceof Durable) {
					$sacrificeUses = $this->getUses($sacrifice);
					$cost += (2 ** $sacrificeUses) - 1;
				}
				if($this->shouldRepair($target, $sacrifice)) {
					$cost += 2;
				}
			}
		}
		if($this->name !== "") {
			$cost += 1;
		}
		return $cost;
	}

	public function calculateRepairCost(int $uses): int {
		return (2 ** $uses) - 1;
	}

	public function checkResult(): void {
		if(!$this->hasResult()) {
			throw new TransactionValidationException("Transaction has no pending result");
		}
		//TODO: We don't always have a durable item... Anything can be renamed
		$result = $this->calculateResult($this->target, $this->sacrifice);
		$this->setUses($this->result, $this->getUses($result));
		if(!$result->equalsExact($this->result)) {
			throw new TransactionValidationException("Calculated result ($result) does not match output item ($this->result)");
		}
	}

	public function calculateResult(Durable $target, ?Item $sacrifice = null): Item {
		$output = clone $target;
		if($this->name !== "") {
			$output->setCustomName($this->name);
		}
		$uses = $this->getUses($target);
		if($sacrifice !== null) {
			$output->setDamage($this->calculateDurability($target, $sacrifice));
			if($sacrifice instanceof Durable && $sacrifice->equals($target, false, false)) {
				if($sacrifice->hasEnchantments()) {
					foreach($sacrifice->getEnchantments() as $sacrificeEnchantment) {
						$enchantmentType = $sacrificeEnchantment->getType();

						if($output->hasEnchantment($enchantmentType)) {
							$sacrificeLevel = $sacrificeEnchantment->getLevel();
							$currentLevel = $output->getEnchantmentLevel($enchantmentType);
							$level = $sacrificeLevel > $currentLevel ? $sacrificeLevel : ($currentLevel === $sacrificeLevel ? $currentLevel + 1 : $currentLevel);
							$output->addEnchantment(new EnchantmentInstance($enchantmentType, min($level, $enchantmentType->getMaxLevel())));
						} elseif($this->isCompatible($target, $enchantmentType)) {
							$output->addEnchantment(clone $sacrificeEnchantment);
						}
					}
				}
				$uses = max($uses, $this->getUses($sacrifice));
			}
		}
		$newUses = $uses + 1;
		$this->setUses($output, $newUses);
		$cost = $this->calculateRepairCost($newUses);
		$this->setRepairCost($output, $cost);
		return $output;
	}

	/**
	 * TODO: Multiple material items for repair. This only accepts one right now, and will reject it if done otherwise
	 */
	#[Pure]
	public function calculateDurability(Durable $target, Item $material): int {
		$durability = $target->getDamage();
		if($material instanceof Durable) {
			$durability -= $material->getDamage();
		}
		return max(0, $durability - ($durability * 1.12));
	}

	public function isCompatible(Durable $target, Enchantment $enchantment): bool {
		foreach($target->getEnchantments() as $targetEnchantment) {
			if(IncompatibleEnchantMap::isIncompatible($targetEnchantment->getType(), $enchantment)) {
				return false;
			}
		}
		return true;
	}

	public function shouldRepair(Durable $target, Item $sacrifice): bool {
		if($target->getDamage() <= 0) {
			return false;
		}

		if($target->equals($sacrifice, false, false)) {
			return true;
		}
		if($target instanceof TieredTool) {
			$material = match ($target->getTier()->id()) {
				ToolTier::WOOD()->id() => VanillaBlocks::OAK_PLANKS()->asItem(),
				ToolTier::STONE()->id() => VanillaBlocks::COBBLESTONE()->asItem(),
				ToolTier::GOLD()->id() => VanillaItems::GOLD_INGOT(),
				ToolTier::IRON()->id() => VanillaItems::IRON_INGOT(),
				ToolTier::DIAMOND()->id() => VanillaItems::DIAMOND(),
				default => null
			};
			return $material !== null && $sacrifice->equals($material, false, false);
		} elseif($target instanceof Armor) {
			$material = match ($target->getId()) {
				ItemIds::LEATHER_CAP, ItemIds::LEATHER_TUNIC, ItemIds::LEATHER_PANTS, ItemIds::LEATHER_BOOTS => VanillaItems::LEATHER(),
				ItemIds::IRON_HELMET, ItemIds::IRON_CHESTPLATE, ItemIds::IRON_LEGGINGS, ItemIds::IRON_BOOTS => VanillaItems::IRON_INGOT(),
				ItemIds::GOLD_HELMET, ItemIds::GOLD_CHESTPLATE, ItemIds::GOLD_LEGGINGS, ItemIds::GOLD_BOOTS => VanillaItems::GOLD_INGOT(),
				ItemIds::DIAMOND_HELMET, ItemIds::DIAMOND_CHESTPLATE, ItemIds::DIAMOND_LEGGINGS, ItemIds::DIAMOND_BOOTS => VanillaItems::DIAMOND(),
				default => null,
			};
			return $material !== null && $sacrifice->equals($material, false, false);
		}
		return false;
	}

	public function getRepairCost(Item $item): int {
		return $item->getNamedTag()->getInt(self::COST_TAG, 0);
	}

	public function setRepairCost(Item $item, int $cost): void {
		if($cost <= 0) {
			$item->getNamedTag()->removeTag(self::COST_TAG);
			return;
		}
		$item->getNamedTag()->setInt(self::COST_TAG, $cost);
	}

	/*
	 * We should probably save the Uses tag if we have it, and if not,
	 * we can save the repair cost
	 */
	public function getUses(Item $item): int {
		if(($uses = $item->getNamedTag()->getInt(self::USES_TAG, -1)) !== -1) {
			return $uses;
		}
		$repairCost = $this->getRepairCost($item);
		$uses = log($repairCost + 1) / log(2);
		$item->getNamedTag()->setInt(self::USES_TAG, $uses);
		return $uses;
	}

	public function setUses(Item $item, int $uses): void {
		if($uses <= 0) {
			$item->getNamedTag()->removeTag(self::USES_TAG);
			return;
		}
		$item->getNamedTag()->setInt(self::USES_TAG, $uses);
	}

	public function onSuccess(AnvilInventory $inventory): void {
		$cost = $this->calculateTransactionCost($this->target, $this->sacrifice);
		// used to compare what the client sends us
		$this->cost = $cost;
		if(!$this->source->isCreative()) {
			$this->source->getXpManager()->subtractXpLevels($this->cost);
		}
		$inventory->onSuccess($this->source);
	}

}