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
		EnchantmentIds::PROTECTION => [EnchantmentIds::FIRE_PROTECTION, EnchantmentIds::BLAST_PROTECTION, EnchantmentIds::PROJECTILE_PROTECTION],
		EnchantmentIds::FIRE_PROTECTION => [EnchantmentIds::PROTECTION, EnchantmentIds::BLAST_PROTECTION, EnchantmentIds::PROJECTILE_PROTECTION],
		//EnchantmentIds::FEATHER_FALLING => [],
		EnchantmentIds::BLAST_PROTECTION => [EnchantmentIds::PROTECTION, EnchantmentIds::FIRE_PROTECTION, EnchantmentIds::PROJECTILE_PROTECTION],
		EnchantmentIds::PROJECTILE_PROTECTION => [EnchantmentIds::PROTECTION, EnchantmentIds::FIRE_PROTECTION, EnchantmentIds::BLAST_PROTECTION],
		//EnchantmentIds::THORNS => [],
		//EnchantmentIds::RESPIRATION => [],
		EnchantmentIds::DEPTH_STRIDER => [EnchantmentIds::FROST_WALKER],
		//EnchantmentIds::AQUA_AFFINITY => [],
		EnchantmentIds::SHARPNESS => [EnchantmentIds::SMITE, EnchantmentIds::BANE_OF_ARTHROPODS],
		EnchantmentIds::SMITE => [EnchantmentIds::SHARPNESS, EnchantmentIds::BANE_OF_ARTHROPODS],
		EnchantmentIds::BANE_OF_ARTHROPODS => [EnchantmentIds::SHARPNESS, EnchantmentIds::SMITE],
		//EnchantmentIds::KNOCKBACK => [],
		//EnchantmentIds::FIRE_ASPECT => [],
		EnchantmentIds::LOOTING => [EnchantmentIds::SILK_TOUCH],
		//EnchantmentIds::EFFICIENCY => [],
		EnchantmentIds::SILK_TOUCH => [EnchantmentIds::FORTUNE, EnchantmentIds::LOOTING, EnchantmentIds::LUCK_OF_THE_SEA],
		//EnchantmentIds::UNBREAKING => [],
		EnchantmentIds::FORTUNE => [EnchantmentIds::SILK_TOUCH],
		//EnchantmentIds::POWER => [],
		//EnchantmentIds::PUNCH => [],
		//EnchantmentIds::FLAME => [],
		EnchantmentIds::INFINITY => [EnchantmentIds::MENDING],
		EnchantmentIds::LUCK_OF_THE_SEA => [EnchantmentIds::SILK_TOUCH],
		//EnchantmentIds::LURE => [],
		EnchantmentIds::FROST_WALKER => [EnchantmentIds::DEPTH_STRIDER],
		EnchantmentIds::MENDING => [EnchantmentIds::INFINITY],
		//EnchantmentIds::BINDING => [],
		//EnchantmentIds::VANISHING => [],
		//EnchantmentIds::IMPALING => [],
		EnchantmentIds::RIPTIDE => [EnchantmentIds::LOYALTY, EnchantmentIds::CHANNELING],
		EnchantmentIds::LOYALTY => [EnchantmentIds::RIPTIDE],
		EnchantmentIds::CHANNELING => [EnchantmentIds::RIPTIDE],
		EnchantmentIds::MULTISHOT => [EnchantmentIds::PIERCING],
		EnchantmentIds::PIERCING => [EnchantmentIds::MULTISHOT],
		//EnchantmentIds::QUICK_CHARGE => [],
		//EnchantmentIds::SOUL_SPEED => []
	];

	public static function isIncompatible(Enchantment $first, Enchantment $second): bool {
		$map = EnchantmentIdMap::getInstance();
		return isset(self::MAP[$map->toId($first)][$map->toId($second)]);
	}

}