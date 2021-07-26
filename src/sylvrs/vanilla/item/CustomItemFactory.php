<?php


namespace sylvrs\vanilla\item;


use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;

final class CustomItemFactory {

	public static function load(): void {
		$factory = ItemFactory::getInstance();
		$factory->register(new EnchantedBook(new ItemIdentifier(ItemIds::ENCHANTED_BOOK, 0), "Enchanted Book"), true);
		$factory->register(new Item(new ItemIdentifier(ItemIds::PHANTOM_MEMBRANE, 0), "Phantom Membrane"), true);
	}

}