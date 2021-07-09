<?php


namespace sylvrs\vanilla\transaction\network;


use pocketmine\network\mcpe\protocol\ServerboundPacket;
use sylvrs\vanilla\transaction\TransactionSession;

abstract class PacketHandler {

	public function __construct(protected string $className, protected TransactionSession $session) {
		$this->className = basename(str_replace("\\", "/", $className));
	}

	public function getClassName(): string {
		return $this->className;
	}

	public function getSession(): TransactionSession {
		return $this->session;
	}

	/**
	 * @return boolean Returns whether or not a packet was handled (false will forward the packet to the server to handle)
	 */
	public abstract function handle(ServerboundPacket $packet): bool;

	public function __destruct() {
		unset($this->className, $this->session);
	}

}