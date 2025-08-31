<?php namespace prison\koth;

use pocketmine\{
	player\Player,
	Server
};
use pocketmine\utils\TextFormat;

class PlayerClaim{

	const TIME_NEEDED = 300; //5 minutes

	public $name;

	public $time;

	public function __construct(Player $player){
		$this->name = $player->getName();
		$this->time = time() + self::TIME_NEEDED;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getPlayer() : ?Player{
		return Server::getInstance()->getPlayerExact($this->getName());
	}

	public function tick() : bool{
		$player = $this->getPlayer();
		if(!$player instanceof Player) return false;

		if(time() >= $this->time){
			$player->sendTip(TextFormat::GREEN . "You won!");
			return true;
		}
		$player->sendTip(TextFormat::AQUA . "Claiming... " . TextFormat::YELLOW . gmdate("i:s", time() - ($this->time - self::TIME_NEEDED)) . TextFormat::GRAY . "/" . TextFormat::GREEN . "05:00");

		return false;
	}

}