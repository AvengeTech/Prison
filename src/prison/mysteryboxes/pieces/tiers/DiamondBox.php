<?php namespace prison\mysteryboxes\pieces\tiers;

use prison\mysteryboxes\pieces\MysteryBox;

class DiamondBox extends MysteryBox{

	public function getSwirlColors() : array{
		return [105,125,255];
	}

	public function getRandomRarity() : int{
		$chance = mt_rand(0,100);
		$rarity = match(true){
			($chance <= 60) => self::RARITY_RARE,
			($chance <= 100) => self::RARITY_LEGENDARY 
		};

		return $rarity;
	}

	public function getTier() : string{
		return "diamond";
	}

}