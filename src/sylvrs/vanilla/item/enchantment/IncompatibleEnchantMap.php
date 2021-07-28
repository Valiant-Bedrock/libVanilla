<?php


namespace sylvrs\vanilla\item\enchantment;


use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\item\enchantment\Enchantment;

final class IncompatibleEnchantMap {

	/**
	 * I hate this... but it works, for now
	 */
	public const MAP = [
		EnchantmentIds::PROTECTION => [EnchantmentIds::FIRE_PROTECTION => true, EnchantmentIds::BLAST_PROTECTION => true, EnchantmentIds::PROJECTILE_PROTECTION => true],
		EnchantmentIds::FIRE_PROTECTION => [EnchantmentIds::PROTECTION => true, EnchantmentIds::BLAST_PROTECTION => true, EnchantmentIds::PROJECTILE_PROTECTION => true],
		//EnchantmentIds::FEATHER_FALLING => [],
		EnchantmentIds::BLAST_PROTECTION => [EnchantmentIds::PROTECTION => true, EnchantmentIds::FIRE_PROTECTION => true, EnchantmentIds::PROJECTILE_PROTECTION => true],
		EnchantmentIds::PROJECTILE_PROTECTION => [EnchantmentIds::PROTECTION => true, EnchantmentIds::FIRE_PROTECTION => true, EnchantmentIds::BLAST_PROTECTION => true],
		//EnchantmentIds::THORNS => [],
		//EnchantmentIds::RESPIRATION => [],
		EnchantmentIds::DEPTH_STRIDER => [EnchantmentIds::FROST_WALKER => true],
		//EnchantmentIds::AQUA_AFFINITY => [],
		EnchantmentIds::SHARPNESS => [EnchantmentIds::SMITE => true, EnchantmentIds::BANE_OF_ARTHROPODS => true],
		EnchantmentIds::SMITE => [EnchantmentIds::SHARPNESS => true, EnchantmentIds::BANE_OF_ARTHROPODS => true],
		EnchantmentIds::BANE_OF_ARTHROPODS => [EnchantmentIds::SHARPNESS => true, EnchantmentIds::SMITE => true],
		//EnchantmentIds::KNOCKBACK => [],
		//EnchantmentIds::FIRE_ASPECT => [],
		EnchantmentIds::LOOTING => [EnchantmentIds::SILK_TOUCH => true],
		//EnchantmentIds::EFFICIENCY => [],
		EnchantmentIds::SILK_TOUCH => [EnchantmentIds::FORTUNE => true, EnchantmentIds::LOOTING => true, EnchantmentIds::LUCK_OF_THE_SEA => true],
		//EnchantmentIds::UNBREAKING => [],
		EnchantmentIds::FORTUNE => [EnchantmentIds::SILK_TOUCH => true],
		//EnchantmentIds::POWER => [],
		//EnchantmentIds::PUNCH => [],
		//EnchantmentIds::FLAME => [],
		EnchantmentIds::INFINITY => [EnchantmentIds::MENDING => true],
		EnchantmentIds::LUCK_OF_THE_SEA => [EnchantmentIds::SILK_TOUCH => true],
		//EnchantmentIds::LURE => [],
		EnchantmentIds::FROST_WALKER => [EnchantmentIds::DEPTH_STRIDER => true],
		EnchantmentIds::MENDING => [EnchantmentIds::INFINITY => true],
		//EnchantmentIds::BINDING => [],
		//EnchantmentIds::VANISHING => [],
		//EnchantmentIds::IMPALING => [],
		EnchantmentIds::RIPTIDE => [EnchantmentIds::LOYALTY => true, EnchantmentIds::CHANNELING => true],
		EnchantmentIds::LOYALTY => [EnchantmentIds::RIPTIDE => true],
		EnchantmentIds::CHANNELING => [EnchantmentIds::RIPTIDE => true],
		EnchantmentIds::MULTISHOT => [EnchantmentIds::PIERCING => true],
		EnchantmentIds::PIERCING => [EnchantmentIds::MULTISHOT => true],
		//EnchantmentIds::QUICK_CHARGE => [],
		//EnchantmentIds::SOUL_SPEED => []
	];

	public static function isIncompatible(Enchantment $first, Enchantment $second): bool {
		$map = EnchantmentIdMap::getInstance();
		$firstId = $map->toId($first);
		$secondId = $map->toId($second);
		return isset(self::MAP[$firstId][$secondId]) || isset(self::MAP[$secondId][$firstId]);
	}

}