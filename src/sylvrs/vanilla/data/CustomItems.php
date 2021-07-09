<?php


namespace sylvrs\vanilla\data;



use pocketmine\item\Item;
use pocketmine\utils\CloningRegistryTrait;

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

	}
}