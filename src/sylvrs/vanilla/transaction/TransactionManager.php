<?php


namespace sylvrs\vanilla\transaction;


use pocketmine\player\Player;
use sylvrs\vanilla\VanillaBase;

class TransactionManager {

	/** @var TransactionSession[] */
	protected array $sessions = [];

	public function __construct(protected VanillaBase $plugin) {
		$plugin->getServer()->getPluginManager()->registerEvents(new TransactionListener($plugin), $plugin);
	}

	public function getPlugin(): VanillaBase {
		return $this->plugin;
	}

	public function get(Player $player): TransactionSession {
		return $this->sessions[$player->getUniqueId()->toString()] ??= new TransactionSession($player);
	}

	public function delete(Player $player): void {
		if(isset($this->sessions[$player->getUniqueId()->toString()])) {
			$this->plugin->getLogger()->debug("Deleting {$player->getName()}'s transaction session...");
			unset($this->sessions[$player->getUniqueId()->toString()]);
		}
	}

	public function clear(): void {
		$this->plugin->getLogger()->debug("Clearing sessions from TransactionManager...");
		$this->sessions = [];
	}
}