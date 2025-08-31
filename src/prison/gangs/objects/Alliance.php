<?php namespace prison\gangs\objects;

use pocketmine\player\Player;

use prison\Prison;

use core\Core;
use core\network\protocol\ServerSubUpdatePacket;
use core\session\mysqli\data\{
	MySqlQuery,
	MySqlRequest
};

class Alliance{

	const SYNC_CREATE = 0;
	const SYNC_DELETE = 1;
	
	public int $created;
	
	public function __construct(
		public int $gangId,
		public int $allyId,
		int $created = 0,
		public bool $new = true
	){
		if(($this->new = $new)){
			$this->created = time();
		}else{
			$this->created = $created;
		}
	}

	public function getGang() : ?Gang{
		return Prison::getInstance()->getGangs()->getGangManager()->getGangById($this->getGangId());
	}

	public function getGangId() : int{
		return $this->gangId;
	}

	public function getAlly() : ?Gang{
		return Prison::getInstance()->getGangs()->getGangManager()->getGangById($this->getAllyId());
	}

	public function getAllyId() : int{
		return $this->allyId;
	}

	public function getCreated() : int{
		return $this->created;
	}

	public function isNew() : bool{
		return $this->new;
	}

	public function sendMessage(Player $player, string $message = "") : bool{
		return $this->getAlly()->sendMessage($player, $message);
	}

	/**
	 * Second parameter in these will remove the need of running twice
	 */
	public function save(bool $saveOpp = true) : void{
		$request = new MySqlRequest("save_alliance_" . $this->getGangId() . "_" . $this->getAllyId(), []);
		$request->addQuery(new MySqlQuery(
			"main",
			"INSERT INTO gang_alliances(gangid, alliance, created) VALUES(?, ?, ?)",
			[
				$this->getGangId(),
				$this->getAllyId(),
				$this->getCreated()
			]
		));
		if($saveOpp){
			$request->addQuery(new MySqlQuery(
				"main",
				"INSERT INTO gang_alliances(gangid, alliance, created) VALUES(?, ?, ?)",
				[
					$this->getAllyId(),
					$this->getGangId(),
					$this->getCreated()
				]
			));	
		}
		Prison::getInstance()->getSessionManager()->sendStrayRequest($request, function(MySqlRequest $request) : void{
			$this->new = false;
		});
	}

	public function delete(bool $delOpp = true, bool $sync = true) : void{
		$gid = $this->getGangId();
		$aid = $this->getAllyId();

		$request = new MySqlRequest("save_alliance_" . $this->getGangId() . "_" . $this->getAllyId(), []);
		$request->addQuery(new MySqlQuery(
			"main",
			"DELETE FROM gang_alliances WHERE gangid=? AND alliance=?",
			[
				$this->getGangId(),
				$this->getAllyId()
			]
		));
		if($delOpp){
			$request->addQuery(new MySqlQuery(
				"main",
				"DELETE FROM gang_alliances WHERE gangid=? AND alliance=?",
				[
					$this->getAllyId(),
					$this->getGangId()
				]
			));
		}
		Prison::getInstance()->getSessionManager()->sendStrayRequest($request, function(MySqlRequest $request) : void{});
		if($sync) $this->sync(self::SYNC_DELETE);
	}

	public function sync(int $type) : void{
		$servers = [];
		foreach(Core::thisServer()->getSubServers(false, true) as $server){
			$servers[] = $server->getIdentifier();
		}
		(new ServerSubUpdatePacket([
			"server" => $servers,
			"type" => "gangSync",
			"data" => [
				"gang" => $this->getGangId(),
				"type" => Gang::SYNC_ALLIANCE,
				"ally" => $this->getAllyId(),
				"change" => $type,
			]
		]))->queue();
	}

	final public function terminate(bool $delOpp = true) : void{
		$gid = $this->getGangId();
		$aid = $this->getAllyId();

		($am = Prison::getInstance()->getGangs()->getGangManager()->getAllianceManager())->removeAlliance($gid, $aid);
		if($delOpp)
			$am->removeAlliance($aid, $gid);
	}

	public function verify() : bool{
		$am = Prison::getInstance()->getGangs()->getGangManager()->getAllianceManager();
		return $am->areAllies($this->getGangId(), $this->getAllyId());
	}

}