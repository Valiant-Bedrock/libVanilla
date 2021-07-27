<?php


namespace sylvrs\vanilla\data;



use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\utils\CloningRegistryTrait;
use sylvrs\vanilla\item\EnchantedBook;

/**
 * This doc-block is generated automatically, do not modify it manually.
 * This must be regenerated whenever registry members are added, removed or changed.
 * @see \pocketmine\utils\RegistryUtils::_generateMethodAnnotations()
 *
 * @method static EnchantedBook ENCHANTED_BOOK()
 * @method static Item PHANTOM_MEMBRANE()
 */
final class CustomItems {
	use CloningRegistryTrait;

	protected static function register(string $name, Item $item): void {
		self::_registryRegister($name, $item);
	}

	public static function fromString(string $name): Item {
		$result = self::_registryFromString($name);
		assert($result instanceof Item);
		return $result;
	}

	/**
	 * @return Item[]
	 */
	public static function getAll(): array {
		return self::_registryGetAll();
	}

	protected static function setup(): void {
		$factory = ItemFactory::getInstance();
		self::register("enchanted_book", $factory->get(ItemIds::ENCHANTED_BOOK));
		self::register("phantom_membrane", $factory->get(ItemIds::PHANTOM_MEMBRANE));
	}
}