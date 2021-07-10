<?php


namespace sylvrs\vanilla\session;


use pocketmine\player\Player;
use sylvrs\vanilla\VanillaBase;

class SessionManager {

	/** @var PlayerSession[] */
	protected array $sessions = [];

	public function __construct(protected VanillaBase $plugin) {}

	public function getPlugin(): VanillaBase {
		return $this->plugin;
	}

	public function get(Player $player): PlayerSession {
		return $this->sessions[$player->getUniqueId()->toString()] ??= new PlayerSession($player);
	}

	public function delete(Player $player): void {
		if(isset($this->sessions[$player->getUniqueId()->toString()])) {
			$session = $this->get($player);
			$session->end();
			$this->plugin->getLogger()->debug("Deleting {$player->getName()}'s session...");
			unset($this->sessions[$player->getUniqueId()->toString()]);
		}
	}

	public function clear(): void {
		$this->plugin->getLogger()->debug("Clearing sessions from SessionManager...");
		$this->sessions = [];
	}
}