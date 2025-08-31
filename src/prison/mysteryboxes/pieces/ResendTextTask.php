<?php namespace prison\mysteryboxes\pieces;

use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class ResendTextTask extends Task{

	public $box;
	public $player;

	public function __construct(MysteryBox $box, Player $player){
		$this->box = $box;
		$this->player = $player;
	}

	public function onRun() : void{
		$this->box->sendText($this->player);
	}

}