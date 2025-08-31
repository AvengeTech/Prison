<?php namespace prison\mysteryboxes\pieces\tiers;

use prison\mysteryboxes\pieces\MysteryBox;

class IronBox extends MysteryBox{

	public function getSwirlColors() : array{
		return [235,235,235];
	}

	public function getRandomRarity() : int{
		$chance = mt_rand(0,100);
		$rarity = match(true){
			($chance <= 40) => self::RARITY_COMMON,
			($chance <= 70) => self::RARITY_UNCOMMON,
			($chance <= 90) => self::RARITY_RARE,
			($chance <= 100) => self::RARITY_LEGENDARY 
		};

		return $rarity;
	}

	public function getTier() : string{
		return "iron";
	}

}