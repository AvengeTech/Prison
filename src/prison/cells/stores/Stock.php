<?php

namespace prison\cells\stores;

use pocketmine\item\{
	Durable,
	Item
};
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use pocketmine\player\Player;

use prison\cells\CellManager;

use core\inbox\Inbox;
use core\inbox\inventory\MessageInventory;
use core\inbox\object\MessageInstance;
use core\ui\elements\simpleForm\Button;
use core\utils\conversion\LegacyItemIds;
use core\utils\TextFormat;

use prison\PrisonPlayer;

class Stock {

	const MAX_STACKS = 10;

	const SALE_NONE = -1;
	const SALE_PERCENT = 0;
	const SALE_AMOUNT = 1;

	public int $rid;

	public function __construct(
		public StockManager $stockManager,
		public int $id,
		public Item $item,

		public int $totalsold,
		public int $available,
		public int $price,

		public string $description = "",

		public int $saleType = self::SALE_NONE,
		public int $saleValue = 0
	) {
		$this->rid = CellManager::newStoreRuntimeId();
	}

	public function getRuntimeId(): int {
		return $this->rid;
	}

	public function getId(): int {
		return $this->id;
	}

	public function getStockManager(): StockManager {
		return $this->stockManager;
	}

	public function getStore(): Store {
		return $this->getStockManager()->getStore();
	}

	public function getItem(): Item {
		return clone $this->item;
	}

	public function stock(Player $player, int $amount = -1): int {
		$item = $this->getItem();
		$stocked = 0;
		while (
			$player->getInventory()->contains($item) &&
			$this->getAvailable() < $this->getMaxAvailable()
		) {
			$player->getInventory()->removeItem($item);
			$this->addAvailable();
			$stocked++;
			if ($amount != -1) {
				$amount--;
				if ($amount <= 0) break;
			}
		}
		return $stocked;
	}

	public function getTotalStockable(Player $player): int {
		$count = 0;
		foreach ($player->getInventory()->all($this->getItem()) as $item) {
			$count += $item->getCount();
		}
		$overcheck = $this->getAvailable() + $count;
		if ($count > $this->getMaxAvailable()) {
			$count -= ($overcheck - $this->getMaxAvailable());
		}
		return $count;
	}

	public function getTotalSold(): int {
		return $this->totalsold;
	}

	public function addTotalSold(int $count = 1): void {
		$this->totalsold += $count;
		$this->getStore()->setChanged();
	}

	public function getMaxAvailable(): int {
		return $this->getItem()->getMaxStackSize() * self::MAX_STACKS;
	}

	public function getAvailable(): int {
		return $this->available;
	}

	public function addAvailable(int $amount = 1): void {
		$this->available += $amount;
		$this->getStore()->setChanged();
	}

	public function takeAvailable(int $amount = 1): void {
		$this->available -= $amount;
		if ($this->available < 0) $this->available = 0;
		$this->getStore()->setChanged();
	}

	public function getBasePrice(): int {
		return $this->price;
	}

	public function setBasePrice(int $price): void {
		$this->price = $price;
		$this->getStore()->setChanged();
	}

	public function getDescription(): string {
		return $this->description;
	}

	public function setDescription(string $description = ""): void {
		$this->description = $description;
		$this->getStore()->setChanged();
	}

	public function getSaleType(): int {
		return $this->saleType;
	}

	public function setSaleType(int $type = self::SALE_NONE): void {
		$this->saleType = $type;
		if ($type == self::SALE_NONE) {
			$this->setSaleValue(0);
		}
		$this->getStore()->setChanged();
	}

	public function isSale(): bool {
		return $this->getSaleType() !== self::SALE_NONE;
	}

	public function getSaleValue(): int {
		return $this->saleValue;
	}

	public function setSaleValue(int $value): void {
		$this->saleValue = $value;
		$this->getStore()->setChanged();
	}

	public function getFormattedSale(): string {
		switch ($this->getSaleType()) {
			case self::SALE_NONE:
				return "No sale";
			case self::SALE_PERCENT:
				return $this->getSaleValue() . "%% off";
			case self::SALE_AMOUNT:
				return $this->getSaleValue() . " techits off";
		}
	}

	public function getFinalPrice(int $count = 1): int {
		$price = $this->getBasePrice();
		if ($this->isSale()) {
			switch ($this->getSaleType()) {
				case self::SALE_PERCENT:
					$price = $price - (($price / 100) * $this->getSaleValue());
					break;

				case self::SALE_AMOUNT:
					$price -= $this->getSaleValue();
			}
		}
		return (int) floor($price * $count);
	}

	public function getButton(): Button {
		$button = new Button(
			$this->getItem()->getName() . TextFormat::RESET . ($this->getAvailable() == 0 ? TextFormat::RED . TextFormat::BOLD . " (Sold out!)" . TextFormat::RESET : "") . TextFormat::DARK_GRAY . PHP_EOL .
				number_format($this->getFinalPrice()) . " techits each" . ($this->isSale() ? " " . TextFormat::BOLD . TextFormat::AQUA . "(SALE)" : "")
		);
		$button->addImage("url", $this->getImage());
		return $button;
	}

	public function getImage(): string {
		$item = $this->getItem();
		return "[REDACTED]" . LegacyItemIds::typeIdToLegacyId($item->getTypeId()) . "-" . ($item instanceof Durable ? 0 : LegacyItemIds::stateIdToMeta($item)) . ".png";
	}

	public function buy(Player $player, int $count = 1): void {
		/** @var PrisonPlayer $player */
		$item = $this->getItem();
		$item->setCount($count);
		$player->getInventory()->addItem($item);

		$this->addTotalSold($count);
		$this->takeAvailable($count);
		$player->takeTechits($price = $this->getFinalPrice($count));

		$store = $this->getStockManager()->getStore();
		$store->addEarnings($price);
	}

	public function withdraw(Player $player, int $amount = 1): int {
		$item = $this->getItem();
		$total = 0;
		while (
			$amount > 0 &&
			$this->getAvailable() > 0 &&
			$player->getInventory()->canAddItem($item)
		) {
			$player->getInventory()->addItem($item);
			$this->takeAvailable();
			$amount--;
			$total++;
		}
		return $total;
	}

	public function delete(Player $player): bool {
		/** @var PrisonPlayer $player */
		$inbox = $player->getSession()->getInbox()->getInbox(Inbox::TYPE_HERE);
		$msg = new MessageInstance($inbox, MessageInstance::newId(), time(), 0, "Cell Stock Items", "Your inventory was full! So we sent the remaining items of your deleted stock to your inbox.", false);
		$leftover = new MessageInventory($msg);
		$available = $this->getAvailable();
		$item = $this->getItem();
		while ($available > 0) {
			if (!$player->getInventory()->canAddItem($item)) {
				$leftover->addItem($item);
			} else {
				$player->getInventory()->addItem($item);
			}
			$available--;
		}
		$msg->setItems($leftover->getContents());

		$this->getStockManager()->removeStock($this);
		$this->getStore()->setChanged();

		if (!empty($msg->getItems())) {
			$inbox->addMessage($msg, true);
			return true;
		}
		return false;
	}

	public function toArray(): array {
		$stream = new BigEndianNbtSerializer();
		$data = [
			"item" => $stream->write(new TreeRoot($this->getItem()->nbtSerialize())),

			"totalsold" => $this->getTotalSold(),
			"available" => $this->getAvailable(),
			"price" => $this->getBasePrice(),

			"description" => $this->getDescription(),

			"saleType" => $this->getSaleType(),
			"saleValue" => $this->getSaleValue()
		];
		return $data;
	}
}
