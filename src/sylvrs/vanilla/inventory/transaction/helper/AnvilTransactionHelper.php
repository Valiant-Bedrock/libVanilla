<?php


namespace sylvrs\vanilla\inventory\transaction\helper;


use pocketmine\block\BlockToolType;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\Armor;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\item\Sword;
use pocketmine\item\TieredTool;
use pocketmine\item\Tool;
use pocketmine\item\ToolTier;
use pocketmine\item\VanillaItems;
use sylvrs\vanilla\data\CustomItems;
use sylvrs\vanilla\inventory\transaction\AnvilTransaction;
use sylvrs\vanilla\item\EnchantedBook;
use sylvrs\vanilla\item\enchantment\IncompatibleEnchantMap;

trait AnvilTransactionHelper {

	public function getItemRepairCost(Item $item): int {
		return $item->getNamedTag()->getInt(AnvilTransaction::COST_TAG, 0);
	}

	public function setItemRepairCost(Item $item, int $cost): void {
		$item->getNamedTag()->setInt(AnvilTransaction::COST_TAG, $cost);
	}

	public function removeItemRepairCost(Item $item): void {
		$item->getNamedTag()->removeTag(AnvilTransaction::COST_TAG);
	}

	public function calculateItemRepairCost(int $uses): int {
		return (2 ** $uses) - 1;
	}

	public function getItemUses(Item $item): int {
		if(($uses = $item->getNamedTag()->getInt(AnvilTransaction::USES_TAG, -1)) !== -1) {
			return $uses;
		}
		$repairCost = $this->getItemRepairCost($item);
		$uses = log($repairCost + 1) / log(2);
		$item->getNamedTag()->setInt(AnvilTransaction::USES_TAG, $uses);
		return $uses;
	}

	public function setItemUses(Item $item, int $uses): void {
		$item->getNamedTag()->setInt(AnvilTransaction::USES_TAG, $uses);
	}

	public function removeItemUses(Item $item): void {
		$item->getNamedTag()->removeTag(AnvilTransaction::USES_TAG);
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

	public function calculateDurability(Durable $target, Item $material): int {
		$durability = $target->getDamage();
		if($material instanceof Durable && $material->equals($target, false, false)) {
			$durability -= $material->getDamage() + (int) floor($target->getMaxDurability() * 0.12);

		} else if($material->equals($this->getRepairItem($target), true, false)) {
			$reductionValue = (int) floor($target->getMaxDurability() * 0.25);
			$count = $material->getCount();
			while($count-- > 0) {
				$durability -= $reductionValue;
			}
		}
		return max(0, $durability);
	}

	public function getRepairAmount(Durable $target, Item $sacrifice): int {
		if($target->getDamage() <= 0) {
			return 0;
		} else if($target->equals($sacrifice, false, false)) {
			return 2;
		}
		$repairItem = $this->getRepairItem($target);
		if($repairItem !== null && $sacrifice->equals($repairItem, false, false)) {
			$damage = $target->getDamage();
			$count = $sacrifice->getCount();
			$cost = 0;
			while($count-- > 0) {
				$damage -= (int) floor($target->getMaxDurability() * 0.25);
				$cost++;
				if($damage <= 0) {
					break;
				}
			}
			return $cost;
		}
		return 0;
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
		} elseif($target->getId() === ItemIds::CROSSBOW && str_contains($enchantment->getName(), "crossbow")) {
			//TODO: Hack! Remove once item flags for crossbows are figured out
			$id = EnchantmentIdMap::getInstance()->toId($enchantment);
			return $id === EnchantmentIds::MULTISHOT || $id === EnchantmentIds::PIERCING || $id === EnchantmentIds::QUICK_CHARGE;
		}
		return false;
	}

	public function isCompatible(Item $target, Enchantment $enchantment): bool {
		foreach($target->getEnchantments() as $targetEnchantment) {
			if (IncompatibleEnchantMap::isIncompatible($targetEnchantment->getType(), $enchantment)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param Item $serverResult
	 * @param Item $clientResult
	 * @return bool - Returns true if the enchants match
	 */
	protected function compareEnchantments(Item $serverResult, Item $clientResult): bool {
		if(count($serverResult->getEnchantments()) !== count($clientResult->getEnchantments())) {
			return false;
		}
		foreach($serverResult->getEnchantments() as $enchantment) {
			if(!$clientResult->hasEnchantment($enchantment->getType(), $enchantment->getLevel())) {
				return false;
			}
		}
		return true;
	}

}