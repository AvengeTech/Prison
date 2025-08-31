<?php namespace prison\gangs\battle;

use prison\Prison;
use prison\gangs\objects\Gang;

use core\Core;
use core\network\protocol\ServerSubUpdatePacket;
use core\utils\TextFormat;

class BattleRequest{

	const SYNC_CREATE = 0;
	const SYNC_DELETE = 1;

	const EXPIRATION = 120;

	public $gang;
	public $requesting;
	public $kit;

	public $mode;
	public $maxParticipants;

	public $created;

	public function __construct(Gang $gang, Gang $requesting, BattleKit $kit, int $mode, int $maxParticipants = 7){
		$this->gang = $gang;
		$this->requesting = $requesting;
		$this->kit = $kit;

		$this->mode = $mode;
		$this->maxParticipants = $maxParticipants;

		$this->created = time();
	}

	public function tick() : bool{
		return
			time() - $this->getCreated() <= self::EXPIRATION &&
			$this->getGang()->getLeaderMember()->isOnline(true) &&
			$this->getRequesting()->getLeaderMember()->isOnline(true);
	}

	public function getGang() : Gang{
		return $this->gang;
	}

	public function getRequesting() : Gang{
		return $this->requesting;
	}

	public function getKit() : BattleKit{
		return $this->kit;
	}

	public function getMode() : int{
		return $this->mode;
	}

	public function getModeName() : string{
		switch($this->getMode()){
			default:
			case Battle::MODE_NO_RESPAWN:
				return "No Respawns";
			case Battle::MODE_LIMITED_RESPAWN:
				return "Limited Respawns (3)";
			case Battle::MODE_RESPAWN:
				return "Respawns";
		}
	}

	public function getMaxParticipants() : int{
		return $this->maxParticipants;
	}

	public function getCreated() : int{
		return $this->created;
	}

	public function accept() : bool{
		$gang = $this->getRequesting();
		$tgang = $this->getGang();
		if($gang->inBattle()){
			if($tgang->getLeader()->isOnline())
				$tgang->getLeader()->getPlayer()->sendMessage(TextFormat::RI . "This gang is already in a Gang Battle!");

			return false;
		}

		$battle = new Battle(Prison::getInstance()->getGangs()->getGangManager()->getBattleManager()->getNewBattleId(), $gang, $tgang, $this->getKit(), $this->getMode(), $this->getMaxParticipants());
		if($battle->getArena() === null){
			if($tgang->getLeader()->isOnline())
				$tgang->getLeader()->getPlayer()->sendMessage(TextFormat::RI . "There were no open arenas. Please try to start a gang battle later!");

			if($gang->getLeader()->isOnline())
				$gang->getLeader()->getPlayer()->sendMessage(TextFormat::RI . "There were no open arenas. Please try to start a gang battle later!");

			return false;
		}

		if(($leader = $gang->getLeader())->isOnline())
			$leader->getPlayer()->sendMessage(TextFormat::RI . "Your Gang Battle request to " . TextFormat::YELLOW . $this->getGang()->getName() . TextFormat::GRAY . " has been accepted!");

		foreach($gang->getMemberManager()->getOnlineMembers() as $member){
			$member->getPlayer()->sendMessage(TextFormat::GI . "Your gang leader has started a gang battle! Type " . TextFormat::YELLOW . "/gg battle" . TextFormat::GRAY . " to enter the battle!");
		}
		foreach($tgang->getMemberManager()->getOnlineMembers() as $member){
			$member->getPlayer()->sendMessage(TextFormat::GI . "Your gang leader has accepted a gang battle! Type " . TextFormat::YELLOW . "/gg battle" . TextFormat::GRAY . " to enter the battle!");
		}

		Prison::getInstance()->getGangs()->getGangManager()->getBattleManager()->addBattle($battle);
		$this->selfDestruct();
		return true;
	}

	public function decline() : void{
		$gang = $this->getRequesting();
		if(($leader = $gang->getLeader())->isOnline())
			$leader->getPlayer()->sendMessage(TextFormat::RI . "Your Gang Battle request to " . TextFormat::YELLOW . $this->getGang()->getName() . TextFormat::GRAY . " has been declined!");

		$this->selfDestruct();
	}

	public function expire() : void{
		$gang = $this->getRequesting();
		if(($leader = $gang->getLeader())->isOnline())
			$leader->getPlayer()->sendMessage(TextFormat::RI . "Your Gang Battle request to " . TextFormat::YELLOW . $this->getGang()->getName() . TextFormat::GRAY . " has expired!");

		$this->selfDestruct();
	}

	public function selfDestruct(bool $sync = true) : void{
		$this->getGang()->getBattleRequestManager()->removeRequest($this);
		if($sync) $this->sync(self::SYNC_DELETE);
	}

	public function verify() : bool{
		return $this->getGang()->getBattleRequestManager()->hasOpenRequest($this->getRequesting());
	}

	public function sync(int $type) : void{
		$servers = [];
		foreach(Core::thisServer()->getSubServers(false, true) as $server){
			$servers[] = $server->getIdentifier();
		}
		$data = [
			"server" => $servers,
			"type" => "gangSync",
			"data" => [
				"gang" => $this->getGang()->getId(),
				"type" => Gang::SYNC_BATTLE_REQUEST,
				"requesting" => $this->getRequesting()->getId(),
				"change" => $type,
			]
		];
		if($type === self::SYNC_CREATE){
			$data["data"]["kit"] = $this->getKit()->getId();
			$data["data"]["mode"] = $this->getMode();
			$data["data"]["max"] = $this->getMaxParticipants();
		}
		(new ServerSubUpdatePacket($data))->queue();
	}

}