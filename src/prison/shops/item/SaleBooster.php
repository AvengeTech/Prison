<?php namespace prison\shops\item;

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

use core\utils\TextFormat;
use pocketmine\nbt\tag\CompoundTag;

class SaleBooster extends Item{

	protected const TAG_INIT = "init";
	protected const TAG_MULTIPLIER = "multiplier";
	protected const TAG_DURATION = "duration";

	private float $multiplier = 1.0;
	private int $duration = 900;


	public function setup(float $multiplier = 1, int $duration = 900) : self{
		$this->multiplier = $multiplier;
		$this->duration = $duration;

		return $this->init();
	}

	public function init() : self{
		$this->getNamedTag()->setByte(self::TAG_INIT, 1);

		$this->setCustomName(TextFormat::RESET . TextFormat::GOLD . "Sale Booster");

		$this->setLore([
			TextFormat::RESET . TextFormat::GRAY . "Tap the ground with this Sale",
			TextFormat::RESET . TextFormat::GRAY . "Booster to gain " . TextFormat::GREEN . $this->getFormattedDuration() . TextFormat::GRAY . " of",
			TextFormat::RESET . TextFormat::GRAY . "autosell and increase the value",
			TextFormat::RESET . TextFormat::GRAY . "of your drops by " . TextFormat::GOLD . (floor($this->getMultiplier() * 100) - 100) . "%" . TextFormat::GRAY . "!",
		]);

		$this->getNamedTag()->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));
		return $this;
	}

	public function isInitiated() : bool{ return (bool) $this->getNamedTag()->getByte(self::TAG_INIT, 0); }

	public function getMultiplier() : float{ return $this->multiplier; }

	public function getDuration() : int{ return $this->duration; }

	public function getFormattedDuration(int $duration = -1): string {
		$duration = $duration < 0 ? $this->getDuration() : $duration;
		$hours = floor($duration / 3600);
		$minutes = floor(($duration / 60) % 60);
		$seconds = $duration % 60;
		if($hours != 0){
			return $hours . " hour" . ($hours > 1 ? "s" : "") . ($minutes != 0 ? ", " . $minutes . " minutes" : "");
		}
		if ($minutes != 0) {
			return $minutes . " minutes";
		}
		return $seconds . " seconds";
	}

	public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems): ItemUseResult{
		/** @var PrisonPlayer $player */
		if(!$this->isInitiated()) return ItemUseResult::FAIL();

		$session = $player->getGameSession()->getShops();

		if($session->isActive()){
			$player->sendMessage(TextFormat::RI . "You already have a " . TextFormat::GOLD . (floor($session->getBoost() * 100) - 100) . "%" . TextFormat::GRAY . " sale booster active!");
			return ItemUseResult::FAIL();
		}

		if (!$session->canBoost()) {
			$player->sendMessage(TextFormat::RI . "You cannot use a sale booster for another " . TextFormat::RED . $this->getFormattedDuration($session->getTimeToNext()) . TextFormat::GRAY . "!");
			return ItemUseResult::FAIL();
		}

		$session->addBoost($this);
		$player->sendMessage(TextFormat::GN . "Your " . TextFormat::GOLD . (floor($this->getMultiplier() * 100) - 100) . "% Sale Booster" . TextFormat::GRAY . " has been applied! It will last for " . TextFormat::GREEN . $this->getFormattedDuration() . "!");
		$this->pop();
		return ItemUseResult::SUCCESS();
	}

	protected function deserializeCompoundTag(CompoundTag $tag) : void{
		parent::deserializeCompoundTag($tag);

		$this->multiplier = $tag->getFloat(self::TAG_MULTIPLIER, 1.0);
		$this->duration = $tag->getInt(self::TAG_DURATION, 900);
	}

	protected function serializeCompoundTag(CompoundTag $tag) : void{
		parent::serializeCompoundTag($tag);

		if($tag->getByte(self::TAG_INIT, 0) === 1)
			$tag->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));

		$tag->setFloat(self::TAG_MULTIPLIER, $this->multiplier);
		$tag->setInt(self::TAG_DURATION, $this->duration);
	}

}