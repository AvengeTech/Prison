<?php namespace prison\mysteryboxes\pieces\tiers;

use prison\mysteryboxes\pieces\MysteryBox;

class GoldBox extends MysteryBox{

	public function getSwirlColors() : array{
		return [235,235,0];
	}

	public function getRandomRarity() : int{
		$chance = mt_rand(0,100);
		$rarity = match(true){
			($chance <= 20) => self::RARITY_COMMON,
			($chance <= 50) => self::RARITY_UNCOMMON,
			($chance <= 80) => self::RARITY_RARE,
			($chance <= 100) => self::RARITY_LEGENDARY 
		};

		return $rarity;
	}

	public function getTier() : string{
		return "gold";
	}

}