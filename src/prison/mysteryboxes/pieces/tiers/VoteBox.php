<?php namespace prison\mysteryboxes\pieces\tiers;

use prison\mysteryboxes\pieces\MysteryBox;

class VoteBox extends MysteryBox{

	public function getSwirlColors() : array{
		return [mt_rand(0,255),mt_rand(0,255),mt_rand(0,255)];
	}

	public function getRandomRarity() : int{
		$chance = mt_rand(0,100);
		$rarity = match(true){
			($chance <= 30) => self::RARITY_RARE,
			($chance <= 98) => self::RARITY_LEGENDARY,
			($chance <= 100) => self::RARITY_VOTE
		};

		return $rarity;
	}

	public function getTier() : string{
		return "vote";
	}

}