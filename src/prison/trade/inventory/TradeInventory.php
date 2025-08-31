<?php

namespace prison\trade\inventory;

use pocketmine\block\{
	VanillaBlocks,
	tile\Nameable,
	tile\Tile
};
use pocketmine\inventory\SimpleInventory;
use pocketmine\item\{
	Durable,
	VanillaItems
};
use pocketmine\network\mcpe\protocol\{
	BlockActorDataPacket,
	ContainerOpenPacket,
	UpdateBlockPacket,
	types\CacheableNbt,
	types\BlockPosition,
	types\inventory\WindowTypes
};
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\world\Position;

use prison\mysteryboxes\items\KeyNote;
use prison\trade\TradeSession;
use prison\techits\item\TechitNote;

use core\Core;
use core\inbox\object\MessageInstance;
use core\discord\objects\{
	Post,
	Embed,
	Field,
	Footer,
	Webhook
};
use core\utils\TextFormat;
use pocketmine\block\utils\DyeColor;
use pocketmine\network\mcpe\convert\TypeConverter;

class TradeInventory extends SimpleInventory {

	public int $ticks = 0;
	public CompoundTag $nbt;

	public function __construct(public TradeSession $session) {
		parent::__construct(27);
		$this->nbt = CompoundTag::create()->setString(Tile::TAG_ID, "Chest")->setString(Nameable::TAG_CUSTOM_NAME, $this->getTitle())->setInt(Tile::TAG_X, 0)->setInt(Tile::TAG_Y, 0)->setInt(Tile::TAG_Z, 0);
	}

	public function setup(): void {
		foreach ($this->getNoTouchSlots() as $slot) {
			if ($slot === $this->getTimerSlot()) {
				$item = VanillaBlocks::WOOL()->setColor(DyeColor::YELLOW())->asItem()->setCount(5);
				$item->setCustomName(TextFormat::RESET . TextFormat::GRAY . "Trade Timer");
			} else {
				$item = VanillaBlocks::WOOL()->setColor(DyeColor::YELLOW())->asItem();
				$item->setCustomName(" ");
			}
			$this->setItem($slot, $item);
		}
		$item = VanillaBlocks::WOOL()->setColor(DyeColor::RED())->asItem();
		$item->setCustomName(TextFormat::RESET . TextFormat::RED . "Not Ready");

		$this->setItem($this->getPlayer1ButtonSlot(), $item);
		$this->setItem($this->getPlayer2ButtonSlot(), $item);
	}

	public function tick(): bool {
		$this->ticks++;
		if ($this->ticks % 2 !== 0) return false; //Ticked twice, so this is a stopper

		if ($this->is1Toggled() && $this->is2Toggled()) {
			$timer = $this->getItem($this->getTimerSlot());
			$count = $timer->getCount();
			if ($count == 1) {
				$this->tradeItems();
				return true;
			} else {
				$count--;
				$item = VanillaBlocks::WOOL()->setColor(DyeColor::YELLOW())->asItem()->setCount($count);
				$item->setCustomName(TextFormat::RESET . TextFormat::GRAY . "Trading in " . $count . "...");

				$this->setItem($this->getTimerSlot(), $item);
				return false;
			}
		} else {
			$timer = $this->getItem($this->getTimerSlot());
			if ($timer->getCount() !== 5) {
				$item = VanillaBlocks::WOOL()->setColor(DyeColor::YELLOW())->asItem()->setCount(5);
				$item->setCustomName(TextFormat::RESET . TextFormat::GRAY . "Trade Timer");

				$this->setItem($this->getTimerSlot(), $item);
				return false;
			}
		}
		return false;
	}

	public function tradeItems(): void {
		$session = $this->getTradeSession();

		$player1 = $session->getPlayer1();
		$player2 = $session->getPlayer2();

		if ($player1 === null || $player2 === null) {
			$this->returnItems();
			return;
		}

		$items = [];
		$player1list = "";
		foreach ($this->getPlayer1ItemSlots() as $slot) {
			$item = $this->getItem($slot);
			if (!$item->equals(VanillaItems::AIR())) {
				if ($player2->getInventory()->canAddItem($item)) {
					$player2->getInventory()->addItem($item);
				} else {
					$items[] = $item;
				}
				$player1list .= "x" . $item->getCount() . " " . TextFormat::clean($item->getName()) .
					($item instanceof TechitNote ? " (" . number_format($item->getTechits()) . " techits)" : ($item instanceof KeyNote ? " (" . number_format($item->getWorth()) . " " . ucfirst($item->getType()) . " keys)" : ($item instanceof Durable ?
								($item->hasEnchantments() ? " (" . count($item->getEnchantments()) . " enchantments)" : "")
								: "")
						)
					) . PHP_EOL;
			}
		}
		if (count($items) > 0) {
			($inbox = $player2->getSession()->getInbox()->getInbox(1))->addMessage(new MessageInstance(
				$inbox,
				MessageInstance::newId(),
				time(),
				0,
				"Trade items",
				"Your inventory was full, so your trade items were sent to your inbox!",
				true,
				false,
				$items
			));
			$player2->sendMessage(TextFormat::YI . "Your inventory was full, so some trade items were sent to your inbox");
		}

		$items = [];
		$player2list = "";
		foreach ($this->getPlayer2ItemSlots() as $slot) {
			$item = $this->getItem($slot);
			if (!$item->equals(VanillaItems::AIR())) {
				if ($player1->getInventory()->canAddItem($item)) {
					$player1->getInventory()->addItem($item);
				} else {
					$items[] = $item;
				}
				$player2list .= "x" . $item->getCount() . " " . TextFormat::clean($item->getName()) .
					($item instanceof TechitNote ? " (" . number_format($item->getTechits()) . " techits)" : ($item instanceof KeyNote ? " (" . number_format($item->getWorth()) . " " . ucfirst($item->getType()) . " keys)" : ($item instanceof Durable ?
								($item->hasEnchantments() ? " (" . count($item->getEnchantments()) . " enchantments)" : "")
								: "")
						)
					) . PHP_EOL;
			}
		}
		if (count($items) > 0) {
			($inbox = $player1->getSession()->getInbox()->getInbox(1))->addMessage(new MessageInstance(
				$inbox,
				MessageInstance::newId(),
				time(),
				0,
				"Trade items",
				"Your inventory was full, so your trade items were sent to your inbox!",
				true,
				false,
				$items
			));
			$player1->sendMessage(TextFormat::YI . "Your inventory was full, so some trade items were sent to your inbox");
		}

		$session->complete();

		$post = new Post("", "Trade Log - " . Core::getInstance()->getNetwork()->getIdentifier(), "[REDACTED]", false, "", [
			new Embed("", "rich", "**" . $player1->getName() . "** just completed a trade with **" . $player2->getName() . "**", "", "ffb106", new Footer("Vlumpkin | " . date("F j, Y, g:ia", time())), "", "[REDACTED]", null, [
				new Field($player1->getName() . "'s offer", $player1list, true),
				new Field($player2->getName() . "'s offer", $player2list, true),
			])
		]);
		$post->setWebhook(Webhook::getWebhookByName("trade-log"));
		$post->send();
	}

	public function returnItems(): void {
		$session = $this->getTradeSession();

		$player1 = $session->getPlayer1();
		$player2 = $session->getPlayer2();

		if ($player1 instanceof Player) {
			$items = [];
			foreach ($this->getPlayer1ItemSlots() as $slot) {
				$item = $this->getItem($slot);
				if (!$item->equals(VanillaItems::AIR())) {
					if ($player1->getInventory()->canAddItem($item)) {
						$player1->getInventory()->addItem($item);
					} else {
						$items[] = $item;
					}
				}
			}
			if ($ni = count($items) > 0) {
				($inbox = $player1->getSession()->getInbox()->getInbox(1))->addMessage(new MessageInstance(
					$inbox,
					MessageInstance::newId(),
					time(),
					0,
					"Trade items",
					"Your inventory was full, so your trade items were returned to your inbox!",
					true,
					false,
					$items
				));
			}
			$player1->sendMessage(TextFormat::RI . "Items from trade have been returned." . ($ni ? " Some were sent to your inbox." : ""));
		}

		if ($player2 instanceof Player) {
			$items = [];
			foreach ($this->getPlayer2ItemSlots() as $slot) {
				$item = $this->getItem($slot);
				if (!$item->equals(VanillaItems::AIR())) {
					if ($player2->getInventory()->canAddItem($item)) {
						$player2->getInventory()->addItem($item);
					} else {
						$items[] = $item;
					}
				}
			}
			if ($ni = count($items) > 0) {
				($inbox = $player2->getSession()->getInbox()->getInbox(1))->addMessage(new MessageInstance(
					$inbox,
					MessageInstance::newId(),
					time(),
					0,
					"Trade items",
					"Your inventory was full, so your trade items were returned to your inbox!",
					true,
					false,
					$items
				));
			}
			$player2->sendMessage(TextFormat::RI . "Items from trade have been returned." . ($ni ? " Some were sent to your inbox." : ""));
		}

		$this->getTradeSession()->complete();
	}

	public function getTradeSession(): TradeSession {
		return $this->session;
	}

	public function getNoTouchSlots(): array {
		return [4, 13, 22];
	}

	public function getTimerSlot(): int {
		return 13;
	}

	public function getPlayer1ButtonSlot(): int {
		return 21;
	}

	public function getPlayer1ItemSlots(): array {
		return [
			0,
			1,
			2,
			3,
			9,
			10,
			11,
			12,
			18,
			19,
			20
		];
	}

	public function is1Toggled(): bool {
		$slot = $this->getItem($this->getPlayer1ButtonSlot());
		return $slot->equals(VanillaBlocks::WOOL()->setColor(DyeColor::LIME())->asItem(), false, false);
	}

	public function toggle1(): void {
		$slot = $this->getPlayer1ButtonSlot();

		$block = VanillaBlocks::WOOL();
		$item = ($this->getItem($slot)->equals(VanillaBlocks::WOOL()->setColor(DyeColor::LIME())->asItem(), false, false) ? $block->setColor(DyeColor::RED())->asItem() : $block->setColor(DyeColor::LIME())->asItem());
		$item->setCustomName(TextFormat::RESET . ($this->getItem($slot)->equals(VanillaBlocks::WOOL()->setColor(DyeColor::RED())->asItem(), false, false) ? TextFormat::RED . "Not ready" : TextFormat::GREEN . "Ready"));

		$this->setItem($slot, $item);
	}

	public function getPlayer2ButtonSlot(): int {
		return 26;
	}

	public function getPlayer2ItemSlots(): array {
		return [
			5,
			6,
			7,
			8,
			14,
			15,
			16,
			17,
			23,
			24,
			25
		];
	}

	public function is2Toggled(): bool {
		$slot = $this->getItem($this->getPlayer2ButtonSlot());
		return $slot->equals(VanillaBlocks::WOOL()->setColor(DyeColor::LIME())->asItem(), false, false);
	}

	public function toggle2(): void {
		$slot = $this->getPlayer2ButtonSlot();

		$block = VanillaBlocks::WOOL();
		$item = ($this->getItem($slot)->equals(VanillaBlocks::WOOL()->setColor(DyeColor::LIME())->asItem(), false, false) ? $block->setColor(DyeColor::RED())->asItem() : $block->setColor(DyeColor::LIME())->asItem());
		$item->setCustomName(TextFormat::RESET . ($this->getItem($slot)->equals(VanillaBlocks::WOOL()->setColor(DyeColor::RED())->asItem(), false, false) ? TextFormat::RED . "Not ready" : TextFormat::GREEN . "Ready"));

		$this->setItem($slot, $item);
	}

	public function getNetworkType(): int {
		return WindowTypes::CONTAINER;
	}

	public function getName(): string {
		return "TradeInventory";
	}

	public function getDefaultSize(): int {
		return 27;
	}

	public function getTitle(): string {
		return "Trade Session";
	}

	public function onOpen(Player $who): void {
		$id = $who->getNetworkSession()->getInvManager()->getWindowId($this);
		if ($id === null) return;

		parent::onOpen($who);
		$pos = new Position($who->getPosition()->getFloorX(), $who->getPosition()->getFloorY() + 2, $who->getPosition()->getFloorZ(), $who->getWorld());

		$this->nbt->setInt(Tile::TAG_X, $pos->x);
		$this->nbt->setInt(Tile::TAG_Y, $pos->y);
		$this->nbt->setInt(Tile::TAG_Z, $pos->z);

		$pk = new UpdateBlockPacket();
		$pk->blockPosition = new BlockPosition($pos->x, $pos->y, $pos->z);
		$pk->blockRuntimeId = TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId(VanillaBlocks::CHEST()->getStateId());
		$who->getNetworkSession()->sendDataPacket($pk);

		$pk = new BlockActorDataPacket();
		$pk->blockPosition = new BlockPosition($pos->x, $pos->y, $pos->z);
		$pk->nbt = new CacheableNbt($this->nbt);
		$who->getNetworkSession()->sendDataPacket($pk);

		$pk = new ContainerOpenPacket();
		$pk->blockPosition = new BlockPosition($pos->x, $pos->y, $pos->z);
		$pk->windowId = $id;
		$pk->windowType = $this->getNetworkType();
		$who->getNetworkSession()->sendDataPacket($pk);
		$who->getNetworkSession()->getInvManager()->syncContents($this);
	}

	public function onClose(Player $who): void {
		parent::onClose($who);
		$pos = new Position($this->nbt->getInt(Tile::TAG_X), $this->nbt->getInt(Tile::TAG_Y), $this->nbt->getInt(Tile::TAG_Z), $who->getWorld());

		$this->nbt->setInt(Tile::TAG_X, 0);
		$this->nbt->setInt(Tile::TAG_Y, 0);
		$this->nbt->setInt(Tile::TAG_Z, 0);

		$pk = new UpdateBlockPacket();
		$pk->blockRuntimeId = TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId(VanillaBlocks::CHEST()->getStateId());
		$pk->blockPosition = new BlockPosition($pos->x, $pos->y, $pos->z);
		$who->getNetworkSession()->sendDataPacket($pk);
	}
}
