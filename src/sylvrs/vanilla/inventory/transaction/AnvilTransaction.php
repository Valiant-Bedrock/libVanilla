<?php


namespace sylvrs\vanilla\inventory\transaction;


use pocketmine\block\BlockToolType;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\item\Armor;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\item\Sword;
use pocketmine\item\TieredTool;
use pocketmine\item\Tool;
use pocketmine\item\ToolTier;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use sylvrs\vanilla\data\CustomItems;
use sylvrs\vanilla\inventory\AnvilInventory;
use sylvrs\vanilla\item\EnchantedBook;
use sylvrs\vanilla\item\enchantment\IncompatibleEnchantMap;
use sylvrs\vanilla\transaction\TransactionManager;

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

	public function __construct(Player $source, protected TransactionManager $session, array $actions = []) {
		parent::__construct($source, $actions);
	}

	public function validate(): void {
		$this->squashDuplicateSlotChanges();

		if(count($this->actions) < 2) {
			throw new TransactionValidationException("Transaction must have at least two actions to be executable");
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

	public function checkResult(): void {
		if(!$this->hasResult()) {
			throw new TransactionValidationException("Transaction has no pending result");
		}
		$result = $this->calculateResult($this->target, $this->sacrifice);
		$this->setUses($this->result, $this->getUses($result));
		if(!$result->equalsExact($this->result)) {
			throw new TransactionValidationException("Calculated result ($result) does not match output item ($this->result)");
		}
	}

	public function calculateResult(Item $target, ?Item $sacrifice = null): Item {
		$output = clone $target;

		if($this->name !== "") {
			$output->setCustomName($this->name);
		}
		$uses = $this->getUses($output);
		if($sacrifice !== null) {
			if($output instanceof Durable) {
				$output->setDamage($this->calculateDurability($output, $sacrifice));
			}
			if($sacrifice->equals($output, false, false) || ($sacrifice->equals(CustomItems::ENCHANTED_BOOK(), true, false))) {
				if($sacrifice->hasEnchantments()) {
					foreach($sacrifice->getEnchantments() as $sacrificeEnchantment) {
						$type = $sacrificeEnchantment->getType();
						if($output->hasEnchantment($type)) {
							$sacrificeLevel = $sacrificeEnchantment->getLevel();
							$outputLevel = $output->getEnchantmentLevel($type);
							$level = $sacrificeLevel > $outputLevel ? $sacrificeLevel : ($outputLevel === $sacrificeLevel ? $outputLevel + 1 : $outputLevel);
							$enchantment = new EnchantmentInstance($type, min($level, $type->getMaxLevel()));
						} elseif($this->isCompatible($output, $type) && $this->canApply($output, $type)) {
							$enchantment = clone $sacrificeEnchantment;
						} else {
							continue;
						}
						$output->addEnchantment($enchantment);
					}
				}
				$uses = max($uses, $this->getUses($sacrifice));
			}
		}
		$uses += 1;
		$this->setUses($output, $uses);
		$this->setRepairCost($output, $this->calculateRepairCost($uses));
		return $output;
	}

	public function calculateDurability(Durable $target, Item $material): int {
		$durability = $target->getDamage();
		if($material instanceof Durable && $material->equals($target, false, false)) {
			$durability -= $material->getDamage();
		} else if($material->equals($this->getRepairItem($target), true, false)) {
			$reductionValue = (int) floor($durability * 0.12);
			$count = $material->getCount();
			while($count-- > 0) {
				$durability -= $reductionValue;
			}
		}
		return max(0, $durability);
	}

	public function isCompatible(Item $target, Enchantment $enchantment): bool {
		if($target->equals(CustomItems::ENCHANTED_BOOK(), false, false)) {
			return true;
		} else {
			foreach($target->getEnchantments() as $targetEnchantment) {
				if (IncompatibleEnchantMap::isIncompatible($targetEnchantment->getType(), $enchantment)) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * TODO: This should be better thought out,
	 * but as not all items are implemented,
	 * this should do fine, for now
	 */
	public function canApply(Item $target, Enchantment $enchantment): bool {
		if($target instanceof EnchantedBook) {
			// Enchanted books can have all enchants applied
			return true;
		} elseif($target instanceof Armor) {
			$flag = match ($target->getArmorSlot()) {
				ArmorInventory::SLOT_HEAD => ItemFlags::HEAD,
				ArmorInventory::SLOT_CHEST => ItemFlags::TORSO,
				ArmorInventory::SLOT_LEGS => ItemFlags::LEGS,
				ArmorInventory::SLOT_FEET => ItemFlags::FEET
			};
			return $enchantment->hasPrimaryItemType($flag);
		} elseif($target instanceof Sword) {
			return $enchantment->hasPrimaryItemType(ItemFlags::SWORD);
		} elseif($target instanceof Tool) {
			$flag = match ($target->getBlockToolType()) {
				BlockToolType::SHOVEL => ItemFlags::SHOVEL,
				BlockToolType::PICKAXE => ItemFlags::PICKAXE,
				BlockToolType::AXE => ItemFlags::AXE,
				default => ItemFlags::TOOL
			};
			return $enchantment->hasPrimaryItemType($flag);
		}
		return false;
	}

	/**
	 * Anvil Calculations:
	 * RepairCost = 2^x - 1, where x is Uses
	 * Uses = log(x + 1) / log(2), where x is RepairCost
	 *
	 * If the player is in survival, the max cost of an anvil is 39 levels.
	 * Otherwise, it's not capped.
	 *
	 * Renaming *always* costs one level.
	 */
	public function calculateTransactionCost(Item $target, ?Item $sacrifice = null): int {
		$targetUses = $this->getUses($target);
		$cost = $this->calculateRepairCost($targetUses);
		if($sacrifice !== null) {
			if($target instanceof Durable) {
				if($sacrifice instanceof Durable) {
					$sacrificeUses = $this->getUses($sacrifice);
					$cost += $this->calculateRepairCost($sacrificeUses);
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

	public function shouldRepair(Durable $target, Item $sacrifice): bool {
		if($target->getDamage() <= 0) {
			return false;
		} else if($target->equals($sacrifice, false, false)) {
			return true;
		}
		$repairItem = $this->getRepairItem($target);
		return $repairItem !== null && $sacrifice->equals($repairItem, false, false);
	}

	public function getRepairItem(Durable $target): ?Item {
		if($target instanceof TieredTool) {
			return match ($target->getTier()->id()) {
				ToolTier::WOOD()->id() => VanillaBlocks::OAK_PLANKS()->asItem(),
				ToolTier::STONE()->id() => VanillaBlocks::COBBLESTONE()->asItem(),
				ToolTier::GOLD()->id() => VanillaItems::GOLD_INGOT(),
				ToolTier::IRON()->id() => VanillaItems::IRON_INGOT(),
				ToolTier::DIAMOND()->id() => VanillaItems::DIAMOND(),
				default => null
			};
		}
		return match ($target->getId()) {
			ItemIds::LEATHER_CAP, ItemIds::LEATHER_TUNIC, ItemIds::LEATHER_PANTS, ItemIds::LEATHER_BOOTS => VanillaItems::LEATHER(),
			ItemIds::IRON_HELMET, ItemIds::IRON_CHESTPLATE, ItemIds::IRON_LEGGINGS, ItemIds::IRON_BOOTS => VanillaItems::IRON_INGOT(),
			ItemIds::GOLD_HELMET, ItemIds::GOLD_CHESTPLATE, ItemIds::GOLD_LEGGINGS, ItemIds::GOLD_BOOTS => VanillaItems::GOLD_INGOT(),
			ItemIds::DIAMOND_HELMET, ItemIds::DIAMOND_CHESTPLATE, ItemIds::DIAMOND_LEGGINGS, ItemIds::DIAMOND_BOOTS => VanillaItems::DIAMOND(),
			// TODO: Netherite items when they're supported
			ItemIds::ELYTRA => CustomItems::PHANTOM_MEMBRANE(),
			ItemIds::TURTLE_HELMET => VanillaItems::SCUTE(),
			default => null
		};
	}

	public function onSuccess(AnvilInventory $inventory): void {
		// used to compare what the client sends us
		$this->cost = $this->calculateTransactionCost($this->target, $this->sacrifice);
		if(!$this->source->isCreative()) {
			$this->source->getXpManager()->subtractXpLevels($this->cost);
		}
		$inventory->onSuccess($this->source);
	}

}