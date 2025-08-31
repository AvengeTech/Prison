<?php

namespace prison\mysteryboxes\items;

use pocketmine\item\{
	Item,
	ItemUseResult
};
use pocketmine\math\Vector3;
use pocketmine\nbt\{
	NBT,
	tag\ListTag
};
use pocketmine\player\Player;

use prison\PrisonPlayer;

use core\Core;
use core\discord\objects\{
	Post,
	Webhook,
	Embed,
	Field,
	Footer
};
use core\utils\TextFormat;
use pocketmine\nbt\tag\CompoundTag;

class KeyNote extends Item {

	protected const TAG_INIT = "init";

	protected const TAG_CREATOR = "creator";
	protected const TAG_KEY_TYPE = "type";
	protected const TAG_KEY_WORTH = "keyworth";

	public function init(): self {
		$colors = [
			"iron" => TextFormat::WHITE,
			"gold" => TextFormat::GOLD,
			"diamond" => TextFormat::AQUA,
			"emerald" => TextFormat::GREEN,
			"divine" => TextFormat::RED,
			"vote" => TextFormat::YELLOW
		];

		$this->setCustomName(TextFormat::RESET . $colors[$this->getType()] . ucfirst($this->getType()) . " Key Note");

		$lores = [];
		$lores[] = "This Key Note is worth";
		$lores[] = $colors[$this->getType()] . number_format($this->getWorth()) . " " . ucfirst($this->getType()) . " keys! " . TextFormat::GRAY . "Right click";
		$lores[] = "the ground to claim your keys!";
		foreach ($lores as $key => $lore) $lores[$key] = TextFormat::RESET . TextFormat::GRAY . $lore;

		$this->setLore($lores);

		$this->getNamedTag()->setByte(self::TAG_INIT, true);
		$this->getNamedTag()->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));
		return $this;
	}

	public function setup(Player|string $player, $type = "iron", int $worth = 1): self {
		$nbt = $this->getNamedTag();
		$nbt->setString(self::TAG_CREATOR, $player instanceof Player ? $player->getName() : $player);
		$nbt->setString(self::TAG_KEY_TYPE, $type);
		$nbt->setInt(self::TAG_KEY_WORTH, $worth);
		$this->setNamedTag($nbt);

		return $this->init();
	}

	public function getCreatedBy(): string {
		return $this->getNamedTag()->getString("creator", "unknown");
	}

	public function getType(): string {
		return $this->getNamedTag()->getString("type", "iron");
	}

	public function getWorth(): int {
		return $this->getNamedTag()->getInt("keyworth", 0);
	}

	public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems): ItemUseResult {
		/** @var PrisonPlayer $player */
		if ($this->getNamedTag()->getByte('init', 0) != 1) return ItemUseResult::FAIL();

		$session = $player->getGameSession()->getMysteryBoxes();
		$before = $session->getKeys($this->getType());
		$session->addKeys($this->getType(), $this->getWorth());
		$after = $session->getKeys($this->getType());

		$colors = [
			"iron" => TextFormat::WHITE,
			"gold" => TextFormat::GOLD,
			"diamond" => TextFormat::AQUA,
			"emerald" => TextFormat::GREEN,
			"divine" => TextFormat::RED,
			"vote" => TextFormat::YELLOW
		];

		$player->sendMessage(TextFormat::GN . "Successfully claimed " . $colors[$this->getType()] . $this->getWorth() . " " . ucfirst($this->getType()) . " keys!");

		$this->pop();
		$player->getInventory()->setItemInHand($this);

		$post = new Post("", "Key Note Log - " . Core::getInstance()->getNetwork()->getIdentifier(), "[REDACTED]", false, "", [
			new Embed("", "rich", "**" . $player->getName() . "** just claimed a Key Note worth **" . number_format($this->getWorth()) . " " . ucfirst($this->getType()) . " keys**", "", "ffb106", new Footer("ok | " . date("F j, Y, g:ia", time())), "", "[REDACTED]", null, [
				new Field("Before", number_format($before), true),
				new Field("After", number_format($after), true),
				new Field("Created by", $this->getCreatedBy(), true),
			])
		]);
		$post->setWebhook(Webhook::getWebhookByName("keynotes-prison"));
		$post->send();

		return ItemUseResult::SUCCESS();
	}

	protected function serializeCompoundTag(CompoundTag $tag): void {
		parent::serializeCompoundTag($tag);

		if ($tag->getByte(self::TAG_INIT, 0) == 1)
			$tag->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));
	}
}
