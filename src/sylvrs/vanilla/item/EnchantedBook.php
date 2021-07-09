<?php


namespace sylvrs\vanilla\item;


use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;

class EnchantedBook extends Item {

	public function __construct(int $meta = 0) {
		parent::__construct(new ItemIdentifier(ItemIds::ENCHANTED_BOOK, $meta), "Enchanted Book");
	}

	public function getMaxStackSize(): int {
		return 1;
	}

}