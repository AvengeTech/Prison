<?php namespace prison\koth\event;

use pocketmine\event\Event;

use prison\koth\Game;

class KothEvent extends Event{

	public $game;

	public function __construct(Game $game){
		$this->game = $game;
	}

	public function getGame() : Game{
		return $this->game;
	}

}