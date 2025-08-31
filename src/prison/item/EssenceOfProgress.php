<?php

namespace prison\item;

use core\utils\TextFormat as TF;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ListTag;

class EssenceOfProgress extends Essence{

	public function getType() : string{ return "p"; }

	public function setup(int $rarity, int $cost = -1, bool $isRaw = true) : self{
		$this->rarity = $rarity;
		$this->isRaw = $isRaw;

		if(!$isRaw){
			$this->cost = ($cost === -1 ? $rarity * mt_rand(5, 7) + mt_rand(0, 4) : $cost);
		}

		return $this;
	}

	public function init() : self{
		$this->getNamedTag()->setByte(self::TAG_INIT, true);
		
		$this->setCustomName(TF::RESET . TF::DARK_PURPLE . ($this->isRaw ? "Raw " : "") . "Essence of Progress");
		$lores = [];

		if($this->isRaw){
			$lores[] = TF::GRAY . "This item must be refined";
			$lores[] = TF::GRAY . "before use.";
			$lores[] = " ";
			$lores[] = TF::GRAY . "Bring this to the " . TF::YELLOW . TF::BOLD . "Refinery" . TF::RESET . TF::GRAY . ",";
			$lores[] = TF::GRAY . "located at " . TF::WHITE . "Spawn";
		}else{
			$lores[] = TF::GRAY . "Use this item to give a book";
			$lores[] = TF::GRAY . "a chance to skip the tier";
			$lores[] = TF::GRAY . "system.";
			$lores[] = " ";
			$lores[] = TF::DARK_AQUA . "Cost: " . $this->cost . " Essence";
			$lores[] = " ";
			$lores[] = TF::GRAY . "Bring this to the " . TF::BLUE . TF::BOLD . "Conjuror" . TF::RESET . TF::GRAY . ",";
			$lores[] = TF::GRAY . "located at " . TF::WHITE . "Spawn";
		}
		foreach($lores as $key => $lore) $lores[$key] = TF::RESET . $lore;

		$this->setLore($lores);
		$this->getNamedTag()->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));

		return $this;
	}
}