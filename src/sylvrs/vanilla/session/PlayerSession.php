<?php


namespace sylvrs\vanilla\session;


use pocketmine\player\Player;
use sylvrs\vanilla\transaction\TransactionManager;

class PlayerSession {

	protected TransactionManager $transactionManager;

	public function __construct(protected Player $player) {
		$this->transactionManager = new TransactionManager($player);
	}

	public function getPlayer(): Player {
		return $this->player;
	}

	public function getTransactionManager(): TransactionManager {
		return $this->transactionManager;
	}

	public function end(): void {
		$this->transactionManager->end();
	}

}