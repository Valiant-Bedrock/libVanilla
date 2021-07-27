<?php


namespace sylvrs\vanilla\block;

use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIdentifier as BID;
use pocketmine\block\BlockLegacyIds as Ids;
use pocketmine\block\BlockToolType;
use pocketmine\block\tile\EnchantTable as TileEnchantingTable;
use pocketmine\item\ToolTier;

final class CustomBlockFactory {

	public static function load(): void {
		$blockFactory = BlockFactory::getInstance();

		$blockFactory->register(new Anvil(new BID(Ids::ANVIL, 0), "Anvil", new BlockBreakInfo(5.0, BlockToolType::PICKAXE, ToolTier::WOOD()->getHarvestLevel(), 6000.0)), true);
		$blockFactory->register(new EnchantingTable(new BID(Ids::ENCHANTING_TABLE, 0, null, TileEnchantingTable::class), "Enchanting Table", new BlockBreakInfo(5.0, BlockToolType::PICKAXE, ToolTier::WOOD()->getHarvestLevel(), 6000.0)), true);
	}

}