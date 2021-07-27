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

	/** @var array[] */
	public const ENCHANTMENT_LIST = [
		EnchantmentIds::DEPTH_STRIDER => ["Depth Strider", Rarity::RARE, ItemFlags::FEET, ItemFlags::NONE, 3],
		EnchantmentIds::AQUA_AFFINITY => ["Aqua Affinity", Rarity::RARE, ItemFlags::HEAD, ItemFlags::NONE, 1],
		EnchantmentIds::SMITE => ["Smite", Rarity::COMMON, ItemFlags::SWORD, ItemFlags::AXE, 5],
		EnchantmentIds::BANE_OF_ARTHROPODS => ["Bane of Arthropods", Rarity::COMMON, ItemFlags::SWORD, ItemFlags::AXE, 5],
		EnchantmentIds::LOOTING => ["%enchantment.looting", Rarity::RARE, ItemFlags::SWORD, ItemFlags::NONE, 3],
		EnchantmentIds::FORTUNE => ["Fortune", Rarity::RARE, ItemFlags::PICKAXE | ItemFlags::SHOVEL | ItemFlags::AXE, ItemFlags::NONE, 3],
		EnchantmentIds::LUCK_OF_THE_SEA => ["Luck of the Sea", Rarity::RARE, ItemFlags::FISHING_ROD, ItemFlags::NONE, 3],
		EnchantmentIds::LURE => ["Lure", Rarity::RARE, ItemFlags::FISHING_ROD, ItemFlags::NONE, 3],
		EnchantmentIds::FROST_WALKER => ["Frost Walker", Rarity::RARE, ItemFlags::FEET, ItemFlags::NONE, 2],
		EnchantmentIds::SOUL_SPEED => ["Soul Speed", Rarity::MYTHIC, ItemFlags::FEET, ItemFlags::NONE, 3]
	];

	public static function load(): void {
		self::addBlacklisted(VanillaEnchantments::KNOCKBACK()); // TODO: Load from configuration
		/** Registration provided by @DrewDoesLife */
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