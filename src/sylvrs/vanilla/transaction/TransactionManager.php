<?php


namespace sylvrs\vanilla\transaction;


use JetBrains\PhpStorm\Pure;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\player\Player;
use sylvrs\vanilla\inventory\HandleableInventory;
use sylvrs\vanilla\inventory\transaction\AnvilTransaction;
use sylvrs\vanilla\inventory\transaction\EnchantingTransaction;
use sylvrs\vanilla\transaction\network\ActorEventHandler;
use sylvrs\vanilla\transaction\network\AnvilDamageHandler;
use sylvrs\vanilla\transaction\network\FilterTextHandler;
use sylvrs\vanilla\transaction\network\InventoryTransactionHandler;
use sylvrs\vanilla\transaction\network\PacketHandler;
use sylvrs\vanilla\VanillaBase;

class TransactionManager {

	private ?AnvilTransaction $anvilTransaction = null;
	private ?EnchantingTransaction $enchantingTransaction = null;

	/** @var PacketHandler[] */
	protected array $handlers = [];

	public function __construct(protected Player $player) {
		$this->addHandlers(
			new ActorEventHandler($this),
			new AnvilDamageHandler($this),
			new InventoryTransactionHandler($this),
			new FilterTextHandler($this)
		);
	}

	public function getPlayer(): Player {
		return $this->player;
	}

	public function addHandler(PacketHandler $handler): void {
		$this->handlers[$handler->getClassName()] = $handler;
	}

	public function addHandlers(PacketHandler ...$handlers): void {
		foreach($handlers as $handler) $this->addHandler($handler);
	}

	public function hasHandler(ServerboundPacket $packet): bool {
		return isset($this->handlers[$packet->getName()]);
	}

	public function getHandler(ServerboundPacket $packet): ?PacketHandler {
		return $this->handlers[$packet->getName()] ?? null;
	}

	/**
	 * Returns true if the player has a window that can be handled, or has an open transaction (anvil or enchanting)
	 */
	#[Pure]
	public function shouldHandle(): bool {
		return $this->player->getCurrentWindow() instanceof HandleableInventory || $this->hasOpenTransaction();
	}

	public function getAnvilTransaction(): ?AnvilTransaction {
		return $this->anvilTransaction;
	}

	public function hasAnvilTransaction(): bool {
		return $this->anvilTransaction instanceof AnvilTransaction;
	}

	public function setAnvilTransaction(AnvilTransaction $anvilTransaction): void {
		$this->anvilTransaction = $anvilTransaction;
	}

	public function removeAnvilTransaction(): void {
		$this->anvilTransaction = null;
	}

	public function getEnchantingTransaction(): ?EnchantingTransaction {
		return $this->enchantingTransaction;
	}

	public function hasEnchantingTransaction(): bool {
		return $this->enchantingTransaction instanceof EnchantingTransaction;
	}

	public function setEnchantingTransaction(EnchantingTransaction $enchantingTransaction): void {
		$this->enchantingTransaction = $enchantingTransaction;
	}

	public function removeEnchantingTransaction(): void {
		$this->enchantingTransaction = null;
	}

	#[Pure]
	public function hasOpenTransaction(): bool {
		return $this->hasEnchantingTransaction() || $this->hasAnvilTransaction();
	}


	public function end(): void {
		$this->removeAnvilTransaction();
		$this->removeEnchantingTransaction();
	}

	public function __destruct() {
		$this->handlers = [];
		unset($this->player, $this->anvilTransaction, $this->enchantingTransaction, $this->handlers);
	}



}