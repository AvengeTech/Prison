<?php namespace prison\gangs\battle\bracket;

use prison\gangs\objects\{
	Gang,
	GangMember
};
use pocketmine\player\Player;

class Participant{

	public $gang;
	public $member;
	public $entry;

	public $kills = 0;
	public $deaths = 0;

	public $stats = [
		1 => [
			"kills" => 0,
			"deaths" => 0,
		],
		2 => [
			"kills" => 0,
			"deaths" => 0,
		],
		3 => [
			"kills" => 0,
			"deaths" => 0,
		],
		4 => [
			"kills" => 0,
			"deaths" => 0,
		],
	];

	public $dead = false;

	public function __construct(Gang $gang, GangMember $member, BracketEntry $entry){
		$this->gang = $gang;
		$this->member = $member;
		$this->entry = $entry;
	}

	public function getGang() : ?Gang{
		return $this->gang;
	}

	public function getMember() : GangMember{
		return $this->member;
	}

	public function getEntry() : BracketEntry{
		return $this->entry;
	}

	public function getPlayer() : ?Player{
		return $this->getMember()->getPlayer();
	}

	public function getName() : string{
		return $this->getMember()->getName();
	}

	public function getXuid() : int{
		return $this->getMember()->getXuid();
	}

	public function isDead() : bool{
		return $this->dead;
	}

	public function setDead(bool $dead = true) : void{
		$this->dead = $dead;
	}

	public function addKill(int $round) : void{
		$this->stats[$round]["kills"]++;
	}

	public function addDeath(int $round) : void{
		$this->stats[$round]["deaths"]++;
	}

}