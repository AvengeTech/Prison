<?php namespace prison\gangs\objects;

use prison\Prison;
use prison\gangs\GangManager;

use core\session\mysqli\data\{
	MySqlQuery,
	MySqlRequest
};
use core\utils\TextFormat;

class AllianceManager{
	
	public array $alliances = [];

	public function __construct(public GangManager $gangManager){}

	public function getGangManager() : GangManager{
		return $this->gangManager;
	}

	public function load(Gang|int $gang, bool $loadAllies = true) : void{
		if($gang instanceof Gang){
			$gangid = $gang->getId();
		}else{
			$gangid = $gang;
		}
		
		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("load_alliances_" . $gangid, new MySqlQuery(
			"main",
			"SELECT * FROM gang_alliances WHERE gangid=?",
			[$gangid]
		)), function(MySqlRequest $request) use($loadAllies, $gangid) : void{
			$rows = $request->getQuery()->getResult()->getRows();
			$alliances = [];
			foreach($rows as $alliance){
				$alliances[] = new Alliance($alliance["gangid"], $alliance["alliance"], $alliance["created"], false);
			}
			$this->alliances[$gangid] = $alliances;
			$gm = $this->getGangManager();
			$gm->getGangById($gangid)->tryLoadedClosure();
			if($loadAllies){
				foreach($alliances as $ally){
					if(!$gm->isLoaded(($aid = $ally->getAllyId())))
						$gm->loadGang($aid);
				}
			}
		});
	}

	public function isLoaded(Gang|int $gang) : bool{
		return isset($this->alliances[($gang instanceof Gang ? $gang->getId() : $gang)]);
	}

	public function getAllAlliances() : array{
		return $this->alliances;
	}

	public function getAlliances(Gang|int $gang, bool $load = false) : array{
		if($load && !$this->isLoaded($gang)) $this->load($gang);

		return $this->alliances[($gang instanceof Gang ? $gang->getId() : $gang)] ?? [];
	}

	public function getAlliancePlayers(Gang|int $gang) : array{
		$players = [];
		foreach($this->getAlliances($gang) as $ally){
			$ag = $ally->getAlly();
			if($ag instanceof Gang){
				foreach($ag->getMemberManager()->getMembers() as $member){
					if($member->isOnline()){
						$players[] = $member->getPlayer();
					}
				}
			}
		}
		return $players;
	}

	public function addAlliance(int $gangId, int $allyId, bool $save = true, bool $sync = true) : bool{
		if($this->areAllies($gangId, $allyId)) return false;

		$ally = new Alliance($gangId, $allyId, 0, true);
		$ally2 = new Alliance($allyId, $gangId, 0, true);
		if($save){
			$ally->save();
			$ally2->save();
		}

		if($sync) $ally->sync(Alliance::SYNC_CREATE);

		$g = $this->getGangManager()->getGangById($gangId);
		$a = $this->getGangManager()->getGangById($allyId);

		if($g != null){
			if(!isset($this->alliances[$gangId]))
				$this->alliances[$gangId] = [];

			$this->alliances[$gangId][] = $ally;

			foreach($g->getMemberManager()->getOnlineMembers() as $member){
				$member->getPlayer()->sendMessage(TextFormat::GI . "Your gang has formed an alliance with " . TextFormat::YELLOW . $a->getName() . TextFormat::GRAY . "!");
			}
		}

		if($a != null){
			if(!isset($this->alliances[$allyId]))
				$this->alliances[$allyId] = [];

			$this->alliances[$allyId][] = $ally2;

			foreach($a->getMemberManager()->getOnlineMembers() as $member){
				$member->getPlayer()->sendMessage(TextFormat::GI . "Your gang has formed an alliance with " . TextFormat::YELLOW . $g->getName() . TextFormat::GRAY . "!");
			}
		}

		return true;
	}

	public function removeAlliance(int $gangId, int $allyId, bool $sync = true) : bool{
		foreach($this->getAlliances($gangId) as $key => $ally){
			if($ally->getAllyId() == $allyId){
				$ally->delete(true, $sync);
				unset($this->alliances[$gangId][$key]);
				return true;
			}
		}
		return false;
	}

	/**
	 * Note: Only works for loaded gangs
	 */
	public function areAllies(Gang|int $gang1, Gang|int $gang2) : bool{
		$allies = $this->getAlliances($gang1);
		foreach($allies as $ally){
			if($ally->getAllyId() == ($gang2 instanceof Gang ? $gang2->getId() : $gang2))
				return true;
		}
		return false;
	}

	public function save(int $gangid, bool $async = false) : void{
		$alliances = $this->getAlliances($gangid);
		foreach($alliances as $key => $ally){
			if(!$ally->new) unset($alliances[$key]);
		}
		if(count($alliances) <= 0) return;

		if($async){
			$request = new MySqlRequest("save_alliances_" . $gangid, []);
			foreach($alliances as $alliance){
				$request->addQuery(new MySqlQuery(
					$gangid . "_" . $alliance->getAllyId(),
					"INSERT INTO gang_alliances(gangid, alliance, created) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE created=VALUES(created)",
					[
						$gangid,
						$alliance->getAllyId(),
						$alliance->getCreated()
					]
				));
			}
			Prison::getInstance()->getSessionManager()->sendStrayRequest($request, function(MySqlRequest $request) : void{});
		}else{
			$db = Prison::getInstance()->getSessionManager()->getDatabase();
			$stmt = $db->prepare("INSERT INTO gang_alliances(gangid, alliance, created) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE created=VALUES(created)");

			foreach($alliances as $ally){
				$aid = $ally->getAllyId();
				$created = $ally->getCreated();
				$stmt->bind_param("iii", $gangid, $aid, $created);
				$stmt->execute();
			}
			$stmt->close();	
		}
	}

	public function saveAll(bool $async = false) : void{
		foreach($this->getAllAlliances() as $id => $allies){
			$this->save($id, $async);
		}
	}

}