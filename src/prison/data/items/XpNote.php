<?php namespace prison\data\items;

use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\item\ItemUseResult;
use pocketmine\player\Player;
use pocketmine\math\Vector3;
use pocketmine\nbt\{
	NBT,
	tag\ListTag
};

use core\utils\TextFormat;

/**
 * @deprecated 1.9.0
 */
class XpNote extends Item{

	public function __construct(){
		parent::__construct(new ItemIdentifier(ItemIds::END_CRYSTAL, 1), "XP Note");
	}

	public function isInitiated(){
		return (bool) $this->getNamedTag()->getByte("init", 0);
	}

	public function init(){
		$nbt = $this->getNamedTag();
		$nbt->setByte("init", 1);
		$this->setNamedTag($nbt);

		$this->setCustomName(TextFormat::RESET . TextFormat::YELLOW . "XP Note");
		$lores = [];
		$lores[] = TextFormat::GRAY . "This XP Note is worth";
		$lores[] = TextFormat::YELLOW . $this->getWorth() . " XP Levels! " . TextFormat::GRAY . "Tap the";
		$lores[] = TextFormat::GRAY . "ground to claim your XP Levels!";
		foreach($lores as $key => $lore) $lores[$key] = TextFormat::RESET . $lore;

		$this->setLore($lores);

		$this->getNamedTag()->setTag("ench", new ListTag([], NBT::TAG_Compound));
	}

	public function setup($worth = 1){
		$nbt = $this->getNamedTag();
		$nbt->setInt("xpworth", $worth);
		$this->setNamedTag($nbt);

		$this->init();
	}

	public function getWorth(){
		return $this->getNamedTag()->getInt("worth", 0);
	}

	public function getXpWorth(){
		return $this->getNamedTag()->getInt("xpworth", 0);
	}

	public function onClickAir(Player $player, Vector3 $directionVector): ItemUseResult{
		if($this->getMeta() != 1) return ItemUseResult::FAIL();

		try{
			$player->getXpManager()->addXpLevels($this->getWorth());
			$player->getXpManager()->addXp($this->getXpWorth());
			$player->sendMessage(TextFormat::GN . "Claimed " . TextFormat::YELLOW . $this->getWorth() . " XP Levels!");
			$this->count--;
			return ItemUseResult::SUCCESS();
		}catch(\InvalidArgumentException $e){
			$player->sendMessage(TextFormat::RN . "You have enough XP...");
			return ItemUseResult::FAIL();
		}
	}

}