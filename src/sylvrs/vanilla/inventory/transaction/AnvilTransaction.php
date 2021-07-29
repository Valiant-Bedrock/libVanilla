<?php


namespace sylvrs\vanilla\inventory\transaction;


use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\player\Player;
use sylvrs\vanilla\data\CustomItems;
use sylvrs\vanilla\inventory\AnvilInventory;
use sylvrs\vanilla\inventory\transaction\helper\AnvilTransactionHelper;
use sylvrs\vanilla\transaction\TransactionManager;

class AnvilTransaction extends InventoryTransaction {
	use AnvilTransactionHelper;

	/** @var string */
	public const COST_TAG = "RepairCost";
	/** @var string */
	public const USES_TAG = "Uses";

	protected string $name = "";
	protected int $cost = -1;

	protected ?Item $target = null;
	protected ?Item $sacrifice = null;

	protected ?Item $result = null;

	public function __construct(Player $source, protected TransactionManager $session, array $actions = []) {
		parent::__construct($source, $actions);
	}

	public function validate(): void {
		$this->squashDuplicateSlotChanges();

		if(count($this->actions) < 2) {
			throw new TransactionValidationException("Transaction must have at least two actions to be executable");
		}
		$haveItems = [];
		$needItems = [];
		$this->matchItems($needItems, $haveItems);

		$this->updateItems();
		if(!$this->hasTarget()) {
			throw new TransactionValidationException("Missing target item for transaction");
		}

		$this->checkResult();
	}

	public function updateItems(): void {
		foreach($this->actions as $action) {
			if($action instanceof SlotChangeAction && $action->getInventory() instanceof AnvilInventory && !$action->getTargetItem()->isNull()) {
				switch($action->getSlot()) {
					case AnvilInventory::TARGET:
						$this->setTarget($action->getTargetItem());
						break;
					case AnvilInventory::SACRIFICE:
						$this->setSacrifice($action->getTargetItem());
						break;
					default:
						// uh, oh. this shouldn't happen
						throw new TransactionValidationException("Invalid slot ({$action->getSlot()}) supplied to anvil transaction");
				}
			}
		}
	}

	public function getName(): string {
		return $this->name;
	}

	public function setName(string $name): void {
		$this->name = $name;
	}

	public function getCost(): int {
		return $this->cost;
	}

	public function setTarget(?Item $target): void {
		$this->target = $target;
	}

	public function hasTarget(): bool {
		return $this->target instanceof Item && !$this->target->isNull();
	}

	public function setSacrifice(?Item $sacrifice): void {
		$this->sacrifice = $sacrifice;
	}

	public function getResult(): ?Item {
		return $this->result;
	}

	public function setResult(?Item $result): void {
		$this->result = $result;
	}

	public function hasResult(): bool {
		return $this->result instanceof Item && !$this->result->isNull();
	}

	public function checkResult(): void {
		if(!$this->hasResult()) {
			throw new TransactionValidationException("Transaction has no pending result");
		}
		$result = $this->calculateResult($this->target, $this->sacrifice);
		// Since the client doesn't inherently use the Uses tag,
		// we'll just attach it before verification
		$this->setItemUses($this->result, $this->getItemUses($result));

		//TODO: Hack! Enchantment sorting sent by the client tends to be weird
		// We'll just remove the enchantments, compare the result of the compound
		// and then check if the item enchantments match
		$baseServerItem = clone $result;
		$baseServerItem->removeEnchantments();

		$baseClientItem = clone $this->result;
		$baseClientItem->removeEnchantments();

		if(!($baseServerItem->equalsExact($baseClientItem) && $this->compareEnchantments($result, $this->result))) {
			throw new TransactionValidationException("Calculated result ($result) does not match output item ($this->result)");
		}
	}

	public function calculateResult(Item $target, ?Item $sacrifice = null): Item {
		$output = clone $target;

		if($this->name !== "") {
			$output->setCustomName($this->name);
		}
		$uses = $this->getItemUses($output);
		if($sacrifice !== null) {
			if($output instanceof Durable) {
				$output->setDamage($this->calculateDurability($output, $sacrifice));
			}
			if($sacrifice->equals($target, false, false) || ($sacrifice->equals(CustomItems::ENCHANTED_BOOK(), true, false))) {
				if($sacrifice->hasEnchantments()) {
					foreach($sacrifice->getEnchantments() as $sacrificeEnchantment) {
						$type = $sacrificeEnchantment->getType();
						if($output->hasEnchantment($type)) {
							$sacrificeLevel = $sacrificeEnchantment->getLevel();
							$outputLevel = $output->getEnchantmentLevel($type);
							$level = $sacrificeLevel > $outputLevel ? $sacrificeLevel : ($outputLevel === $sacrificeLevel ? $outputLevel + 1 : $outputLevel);
							$enchantment = new EnchantmentInstance($type, min($level, $type->getMaxLevel()));
						} elseif($this->canApply($output, $type) && $this->isCompatible($output, $type)) {
							$enchantment = clone $sacrificeEnchantment;
						} else {
							continue;
						}
						$output->addEnchantment($enchantment);
					}
				}
				$uses = max($uses, $this->getItemUses($sacrifice));
			}
			$uses += 1;
			$this->setItemUses($output, $uses);
		}
		$this->setItemRepairCost($output, $this->calculateItemRepairCost($uses));
		return $output;
	}

	/**
	 * Anvil Calculations:
	 * RepairCost = 2^x - 1, where x is Uses
	 * Uses = log(x + 1) / log(2), where x is RepairCost
	 *
	 * If the player is in survival, the max cost of an anvil is 39 levels.
	 * Otherwise, it's not capped.
	 *
	 * Renaming *always* costs one level.
	 */
	public function calculateTransactionCost(Item $target, ?Item $sacrifice = null): int {
		$targetUses = $this->getItemUses($target);
		$cost = $this->calculateItemRepairCost($targetUses);
		if($sacrifice !== null) {
			if($target instanceof Durable) {
				if($sacrifice instanceof Durable) {
					$sacrificeUses = $this->getItemUses($sacrifice);
					$cost += $this->calculateItemRepairCost($sacrificeUses);
				}
				if($this->shouldRepair($target, $sacrifice)) {
					$cost += $this->getRepairAmount($target, $sacrifice);
				}
			}
		}
		if($this->name !== "") {
			$cost += 1;
		}
		return $cost;
	}

	public function onSuccess(AnvilInventory $inventory): void {
		// used to compare what the client sends us
		$this->cost = $this->calculateTransactionCost($this->target, $this->sacrifice);
		if(!$this->source->isCreative()) {
			$this->source->getXpManager()->subtractXpLevels($this->cost);
		}
		$inventory->onSuccess($this->source);
	}

}