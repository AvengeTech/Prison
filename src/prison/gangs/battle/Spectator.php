<?php namespace prison\gangs\battle;

use pocketmine\{
	player\Player,
	Server
};

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\Gang;
use prison\gangs\battle\Battle;

class Spectator{

	public $player;

	public $gang;
	public $battle;

	public function __construct(Player $player, ?Gang $gang, Battle $battle){
		$this->player = $player->getName();

		$this->gang = $gang;
		$this->battle = $battle;
	}

	public function getPlayer() : ?PrisonPlayer{
		return Server::getInstance()->getPlayerExact($this->player);
	}

	public function getGang() : ?Gang{
		return $this->gang instanceof Gang ? Prison::getInstance()->getGangs()->getGangManager()->getGangByGang($this->gang) : null;
	}

	public function getBattle() : ?Battle{
		return Prison::getInstance()->getGangs()->getGangManager()->getBattleManager()->getBattleByBattle($this->battle);
	}

	public function remove(bool $spawn = false) : void{
		$battle = $this->getBattle();
		$player = $this->getPlayer();
		if($battle !== null && $player instanceof Player){
			$battle->removeSpectator($player);
			if($spawn) $player->gotoSpawn();
		}

	}

}