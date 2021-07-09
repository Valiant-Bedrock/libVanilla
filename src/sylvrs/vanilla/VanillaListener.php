<?php


namespace sylvrs\vanilla\listener;


use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;
use sylvrs\vanilla\VanillaBase;

abstract class VanillaListener implements Listener {

	private bool $registered = false;

	public function __construct(protected VanillaBase $plugin) {}

	public function getPlugin(): VanillaBase {
		return $this->plugin;
	}

	public function register(): void {
		if($this->registered) {
			$this->getPlugin()->getLogger()->warning(TextFormat::YELLOW . "Plugin attempted to register an already registered listener!");
			return;
		}
		$this->registered = true;
		$this->getPlugin()->getServer()->getPluginManager()->registerEvents($this, $this->getPlugin());
		$this->getPlugin()->getLogger()->info(sprintf(TextFormat::YELLOW . "%s#%s has been registered!", get_class($this), spl_object_id($this)));
	}

	public function unregister(): void {
		if(!$this->registered) {
			$this->getPlugin()->getLogger()->warning(TextFormat::YELLOW . "Plugin attempted to unregister a listener that wasn't registered!");
			return;
		}
		HandlerListManager::global()->unregisterAll($this);
		$this->registered = false;
	}

}