<?php

namespace prison\item;

use core\utils\TextFormat as TF;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ListTag;
use prison\enchantments\EnchantmentData as ED;

class EssenceOfAscension extends Essence{

	public function getType() : string{ return "a"; }

	public function setup(int $rarity, bool $isRaw = true) : self{
		$this->rarity = $rarity;
		$this->isRaw = $isRaw;

		if(!$isRaw){
			$this->cost = match($rarity){
				ED::RARITY_COMMON => 100,
				ED::RARITY_UNCOMMON => 110,
				ED::RARITY_RARE => 120,
				ED::RARITY_LEGENDARY => 130,
				ED::RARITY_DIVINE => 150,
			};
		}

		return $this;
	}

	public function init() : self{
		$this->getNamedTag()->setByte(self::TAG_INIT, true);
		
		$this->setCustomName(TF::RESET . TF::YELLOW . ($this->isRaw ? "Raw " : "") . "Essence of Ascension");
		$lores = [];

		if($this->isRaw){
			$lores[] = TF::GRAY . "This item must be refined";
			$lores[] = TF::GRAY . "before use.";
			$lores[] = " ";
			$lores[] = TF::AQUA . "Rarity: " . TF::BOLD . $this->getRarityName();
			$lores[] = " ";
			$lores[] = TF::GRAY . "Bring this to the " . TF::YELLOW . TF::BOLD . "Refinery" . TF::RESET . TF::GRAY . ",";
			$lores[] = TF::GRAY . "located at " . TF::WHITE . "Spawn";
		}else{
			$lores[] = TF::GRAY . "Use this on enchanted items";
			$lores[] = TF::GRAY . "to level up an enchantment";
			$lores[] = TF::GRAY . "based on the rarity.";
			$lores[] = " ";
			$lores[] = TF::AQUA . "Rarity: " . TF::BOLD . $this->getRarityName();
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