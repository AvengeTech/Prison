<?php namespace prison\gangs\battle\bracket;

use prison\gangs\objects\{
	Gang,
};
use pocketmine\player\Player;

use core\user\User;

class BracketEntry{

	public $gang;
	public $participants = [];

	public $created = false;

	public $out = false;

	public function __construct(Gang $gang, array $participants, bool $created = false){
		$this->gang = $gang;
		$this->participants = $participants;
		$this->created = $created;
	}

	public function getGang() : Gang{
		return $this->gang;
	}

	public function getParticipants() : array{
		return $this->participants;
	}

	public function getAliveParticipants() : array{
		$pa = [];
		foreach($this->getParticipants() as $p){
			if(!$p->isDead()) $pa[] = $p;
		}
		return $pa;
	}

	public function isParticipant(Player $player) : bool{
		foreach($this->getParticipants() as $p){
			if($p->getXuid() == $player->getXuid()) return true;
		}
		return false;
	}

	public function getParticipant(Player $player) : ?Participant{
		foreach($this->getParticipants() as $p){
			if($p->getXuid() == $player->getXuid()) return $p;
		}
		return null;
	}

	public function addParticipant(Player $player) : bool{
		if(!($g = $this->getGang())->inGang($player)) return false;
		if($this->isParticipant($player)) return false;
		$this->participants[] = new Participant($g, $g->getMemberManager()->getMember($player), $this);
		return true;
	}

	public function removeParticipant($player) : bool{
		$player = new User($player);
		foreach($this->participants as $key => $par){
			if($par->getXuid() == $player->getXuid()){
				unset($this->participants[$key]);
				return true;
			}
		}
		return false;
	}

	public function isCreator() : bool{
		return $this->created;
	}

	public function isOut() : bool{
		return $this->out;
	}

	public function setOut(bool $out = true) : void{
		$this->out = $out;
	}

}