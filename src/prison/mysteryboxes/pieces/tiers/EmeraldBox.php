<?php namespace prison\mysteryboxes\pieces\tiers;

use prison\mysteryboxes\pieces\MysteryBox;

class EmeraldBox extends MysteryBox{

	public function getSwirlColors() : array{
		return [124,252,0];
	}

	public function getRandomRarity() : int{
		return self::RARITY_LEGENDARY;
	}

	public function getTier() : string{
		return "emerald";
	}

}