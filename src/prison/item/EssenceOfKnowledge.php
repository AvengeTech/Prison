<?php namespace prison\item;

use pocketmine\item\{
	Item,
};
use pocketmine\nbt\{
	NBT,
	tag\ListTag
};

use core\utils\TextFormat;

class EssenceOfKnowledge extends Essence{

	public function getType() : string{ return "k"; }

	public function setup(int $rarity, int $cost = -1, bool $isRaw = true) : self{
		$this->rarity = $rarity;
		$this->isRaw = $isRaw;

		if(!$isRaw){
			$this->cost = ($cost === -1 ? $rarity * 5 + (mt_rand(1, 3) * mt_rand(1, 2)) : $cost);
		}
		return $this;
	}

	public function init() : self{
		$this->getNamedTag()->setByte(self::TAG_INIT, true);
		$this->setCustomName(TextFormat::RESET . TextFormat::AQUA . ($this->isRaw ? "Raw " : "") . "Essence of Knowledge");

		$lores = [];

		if($this->isRaw){
			$lores[] = TextFormat::GRAY . "This item must be refined";
			$lores[] = TextFormat::GRAY . "before use.";
			$lores[] = " ";
			$lores[] = TextFormat::GRAY . "Bring this to the " . TextFormat::YELLOW . TextFormat::BOLD . "Refinery" . TextFormat::RESET . TextFormat::GRAY . ",";
			$lores[] = TextFormat::GRAY . "located at " . TextFormat::WHITE . "Spawn";
		}else{
			$lores[] = TextFormat::GRAY . "Use this item to combine two";
			$lores[] = TextFormat::GRAY . "of the same books together";
			$lores[] = TextFormat::GRAY . "or reroll a book";
			$lores[] = " ";
			$lores[] = TextFormat::DARK_AQUA . "Cost: " . $this->cost . " Essence";
			$lores[] = " ";
			$lores[] = TextFormat::GRAY . "Bring this to the " . TextFormat::BLUE . TextFormat::BOLD . "Conjuror" . TextFormat::RESET . TextFormat::GRAY . ",";
			$lores[] = TextFormat::GRAY . "located at " . TextFormat::WHITE . "Spawn";
		}
		foreach($lores as $key => $lore) $lores[$key] = TextFormat::RESET . $lore;

		$this->setLore($lores);
		$this->getNamedTag()->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));

		return $this;
	}
}