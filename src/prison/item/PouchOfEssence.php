<?php namespace prison\item;

use pocketmine\item\{
	Item,
    ItemUseResult,
};
use pocketmine\nbt\{
    NBT,
    tag\ListTag
};

use core\utils\TextFormat;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use prison\enchantments\EnchantmentData;
use prison\PrisonPlayer;

class PouchOfEssence extends Item{

	protected const TAG_INIT = 'init';
	protected const TAG_AMOUNT = 'amount';
	protected const TAG_CREATOR = 'creator';

	private int $amount = 0;
	private string $creator = "CONSOLE";

	public function getMaxStackSize() : int{ return 1; }

	public function getAmount() : int{ return $this->amount; }

	public function getCreator() : string{ return $this->creator; }

	public function isInitiated() : bool{ return (bool) $this->getNamedTag()->getByte(self::TAG_INIT, false); }

	public function setup(Player|string $creator, int $amount) : self{
		$this->creator = ($creator instanceof Player ? $creator->getName() : $creator);
		$this->amount = $amount;

		return $this;
	}

	public function init() : void{
		$this->getNamedTag()->setByte(self::TAG_INIT, true);
		$this->setCustomName(TextFormat::RESET . TextFormat::DARK_AQUA . "Pouch of Essence");

		$lores = [];
		$lores[] = TextFormat::GRAY . "This pouch of essence is worth";
		$lores[] = TextFormat::DARK_AQUA . number_format($this->getAmount()) . " Essence! " . TextFormat::GRAY . "Tap the ground";
		$lores[] = TextFormat::GRAY . "to claim your essence!";;

		foreach($lores as $key => $lore) $lores[$key] = TextFormat::RESET . $lore;

		$this->setLore($lores);
		$this->getNamedTag()->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));
	}

	public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems) : ItemUseResult{
		/** @var PrisonPlayer $player */
		if(!$this->isInitiated()){ // Temp Fix
			$this->setup("Quest Master", 250);
			$this->init();
			return ItemUseResult::SUCCESS();
		}
	
		if(!$this->isInitiated()) return ItemUseResult::FAIL;

		$player->getInventory()->setItemInHand($this->pop(1));
		$player->getGameSession()->getEssence()->addEssence($this->amount);
		$player->sendMessage(TextFormat::GN . "Claimed " . TextFormat::DARK_AQUA . number_format($this->getAmount()) . " Essence!");
		return ItemUseResult::SUCCESS();
	}

	protected function deserializeCompoundTag(CompoundTag $tag) : void{
		parent::deserializeCompoundTag($tag);

		$this->creator = $tag->getString(self::TAG_CREATOR, "Unknown");
		$this->amount = $tag->getInt(self::TAG_AMOUNT, 0);
	}

	protected function serializeCompoundTag(CompoundTag $tag) : void{
		parent::serializeCompoundTag($tag);

		if($tag->getByte(self::TAG_INIT, 0) == 1)
			$tag->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));
			$tag->setString(self::TAG_CREATOR, $this->creator);
			$tag->setInt(self::TAG_AMOUNT, $this->amount);
	}
}