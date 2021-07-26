<?php


namespace sylvrs\vanilla;


use pocketmine\plugin\PluginBase;
use sylvrs\vanilla\item\CustomItemFactory;
use sylvrs\vanilla\item\enchantment\EnchantmentManager;
use sylvrs\vanilla\session\SessionCreationListener;
use sylvrs\vanilla\session\SessionManager;
use sylvrs\vanilla\transaction\TransactionListener;

class VanillaBase extends PluginBase {

	protected static VanillaBase $instance;

	protected SessionManager $sessionManager;

	protected function onLoad(): void {
		self::$instance = $this;
		CustomItemFactory::load();
		EnchantmentManager::load();
	}

	protected function onEnable(): void {
		$this->sessionManager = new SessionManager($this);
		$this->loadListeners();
	}

	public static function getInstance(): VanillaBase {
		return self::$instance;
	}

	public function getSessionManager(): SessionManager {
		return $this->sessionManager;
	}

	public function loadListeners(): void {
		(new SessionCreationListener($this))->register();
		(new TransactionListener($this))->register();
	}

}