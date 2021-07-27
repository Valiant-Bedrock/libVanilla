<?php


namespace sylvrs\vanilla\block;


use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use sylvrs\vanilla\inventory\EnchantInventory;

class EnchantingTable extends \pocketmine\block\EnchantingTable {

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null): bool {
		if($player instanceof Player) {
			$player->setCurrentWindow(new EnchantInventory($this->pos));
		}
		return true;
	}

}