<?php


namespace sylvrs\vanilla\item\enchantment;


use JetBrains\PhpStorm\Pure;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\enchantment\Rarity;
use pocketmine\item\enchantment\VanillaEnchantments;

class EnchantmentManager {

	/** @var int */
	public const SOURCE_TYPE_ENCHANT_INPUT = -15;
	/** @var int */
	public const SOURCE_TYPE_ENCHANT_MATERIAL = -16;
	/** @var int */
	public const SOURCE_TYPE_ANVIL_INPUT = -10;
	/** @var int */
	public const SOURCE_TYPE_ANVIL_MATERIAL = -11;

	/** @var Enchantment[] */
	private static array $BLACKLISTED_ENCHANTS = [];

	/** Registration info provided by @DrewDoesLife */
	public const ENCHANTMENT_LIST = [
		EnchantmentIds::DEPTH_STRIDER => ["%enchantment.waterWalker", Rarity::RARE, ItemFlags::FEET, ItemFlags::NONE, 3],
		EnchantmentIds::AQUA_AFFINITY => ["%enchantment.waterWorker", Rarity::RARE, ItemFlags::HEAD, ItemFlags::NONE, 1],
		EnchantmentIds::SMITE => ["%enchantment.damage.undead", Rarity::COMMON, ItemFlags::SWORD, ItemFlags::AXE, 5],
		EnchantmentIds::BANE_OF_ARTHROPODS => ["%enchantment.damage.arthropods", Rarity::COMMON, ItemFlags::SWORD, ItemFlags::AXE, 5],
		EnchantmentIds::LOOTING => ["%enchantment.looting", Rarity::RARE, ItemFlags::SWORD, ItemFlags::NONE, 3],
		EnchantmentIds::FORTUNE => ["%enchantment.lootBonusDigger", Rarity::RARE, ItemFlags::DIG, ItemFlags::NONE, 3],
		EnchantmentIds::LUCK_OF_THE_SEA => ["%enchantment.lootBonusFishing", Rarity::RARE, ItemFlags::FISHING_ROD, ItemFlags::NONE, 3],
		EnchantmentIds::LURE => ["%enchantment.fishingSpeed", Rarity::RARE, ItemFlags::FISHING_ROD, ItemFlags::NONE, 3],
		EnchantmentIds::FROST_WALKER => ["%enchantment.frostwalker", Rarity::RARE, ItemFlags::FEET, ItemFlags::NONE, 2],
		EnchantmentIds::SOUL_SPEED => ["%enchantment.soul_speed", Rarity::MYTHIC, ItemFlags::FEET, ItemFlags::NONE, 3],
		EnchantmentIds::LOYALTY => ["%enchantment.tridentLoyalty", Rarity::UNCOMMON, ItemFlags::TRIDENT, ItemFlags::NONE, 3],
		EnchantmentIds::CHANNELING => ["%enchantment.tridentChanneling", Rarity::MYTHIC, ItemFlags::TRIDENT, ItemFlags::NONE, 1],
		EnchantmentIds::RIPTIDE => ["%enchantment.tridentRiptide", Rarity::RARE, ItemFlags::TRIDENT, ItemFlags::NONE, 3],
		EnchantmentIds::IMPALING => ["%enchantment.tridentImpaling", Rarity::RARE, ItemFlags::TRIDENT, ItemFlags::NONE, 5]
	];

	public static function load(): void {
		self::addBlacklisted(VanillaEnchantments::KNOCKBACK()); // TODO: Load from configuration
		foreach (self::ENCHANTMENT_LIST as $id => $info) {
			EnchantmentIdMap::getInstance()->register($id, new Enchantment(1000 - $id, ...$info));
		}
	}

	private static function addBlacklisted(Enchantment $enchantment): void {
		self::$BLACKLISTED_ENCHANTS[$enchantment->getName()] = true;
	}

	#[Pure]
	public static function isBlacklisted(Enchantment $enchantment): bool {
		return isset(self::$BLACKLISTED_ENCHANTS[$enchantment->getName()]);
	}

}