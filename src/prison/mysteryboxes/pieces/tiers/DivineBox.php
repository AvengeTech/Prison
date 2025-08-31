<?php namespace prison\mysteryboxes\pieces\tiers;

use prison\mysteryboxes\pieces\MysteryBox;

class DivineBox extends MysteryBox{

	public function getSwirlColors() : array{
		return [124,252,0];
	}

	public function getRandomRarity() : int{
		return self::RARITY_DIVINE;
	}

	public function getTier() : string{
		return "divine";
	}

}