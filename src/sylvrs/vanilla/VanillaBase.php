<?php


namespace sylvrs\vanilla;


use pocketmine\plugin\PluginBase;
use sylvrs\vanilla\item\enchantment\EnchantmentManager;
use sylvrs\vanilla\transaction\TransactionManager;

class VanillaBase extends PluginBase {

	protected static VanillaBase $instance;

	protected TransactionManager $transactionManager;

	protected function onLoad(): void {
		self::$instance = $this;

		EnchantmentManager::load();
	}

	protected function onEnable(): void {
		$this->transactionManager = new TransactionManager($this);
	}

	public static function getInstance(): VanillaBase {
		return self::$instance;
	}

	public function getTransactionManager(): TransactionManager {
		return $this->transactionManager;
	}

}