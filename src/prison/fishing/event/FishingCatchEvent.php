<?php namespace prison\fishing\event;

use pocketmine\event\Event;
use pocketmine\player\Player;

use prison\fishing\object\FishingFind;

class FishingCatchEvent extends Event{

	public function __construct(
		public Player $player,
		public FishingFind $find
	){}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function getFishingFind() : FishingFind{
		return $this->find;
	}

}