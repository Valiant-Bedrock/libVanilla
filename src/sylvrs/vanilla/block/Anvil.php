<?php


namespace sylvrs\vanilla\block;


use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\AnvilBreakSound;
use pocketmine\world\sound\AnvilUseSound;
use sylvrs\vanilla\inventory\AnvilInventory;

class Anvil extends \pocketmine\block\Anvil {

	/** The percentage chance that the anvil will be damaged on use */
	public const DAMAGE_CHANCE = 12;

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null): bool {
		$player->setCurrentWindow(new AnvilInventory($this->pos));
		return true;
	}

	public function onUse(): void {
		$pos = $this->getPos();
		$pos->getWorld()->addSound($this->getPos(), new AnvilUseSound);

		if(mt_rand(0, 100) <= self::DAMAGE_CHANCE) {
			$pos = $this->getPos();
			$damage = $this->getDamage();
			if(++$damage > 2) {
				$pos->getWorld()->setBlock($pos, VanillaBlocks::AIR());
				$pos->getWorld()->addSound($pos, new AnvilBreakSound);
				return;
			}
			$this->setDamage($damage);
		}
	}

}