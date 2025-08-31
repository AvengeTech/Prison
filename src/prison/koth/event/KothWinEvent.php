<?php namespace prison\koth\event;

use pocketmine\player\Player;

use prison\koth\Game;

class KothWinEvent extends KothEvent{

	public $player;

	public function __construct(Game $game, Player $player){
		parent::__construct($game);
		$this->player = $player;
	}

	public function getPlayer() : Player{
		return $this->player;
	}

}