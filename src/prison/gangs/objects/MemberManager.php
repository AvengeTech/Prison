<?php namespace prison\gangs\objects;

use pocketmine\player\Player;

use prison\Prison;

use core\Core;
use core\session\mysqli\data\{
	MySqlQuery,
	MySqlRequest
};
use core\user\User;

class MemberManager{

	const MAX_ELDERS = 2;
	
	public array $members = [];
	
	public bool $loaded = false;

	public function __construct(public Gang $gang){}

	public function getGang() : Gang{
		return $this->gang;
	}

	public function isLoaded() : bool{
		return $this->loaded;
	}

	public function setLoaded(bool $loaded = true) : void{
		$this->loaded = $loaded;
	}

	/**
	 * Loads all gang members at once;
	 * More resource efficient than separately
	 */
	public function load() : void{
		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("load_gang_members_" . $this->getGang()->getId(),
			new MySqlQuery(
				"main",
				"SELECT * FROM gang_members WHERE gangid=?",
				[$this->getGang()->getId()]
			)
		), function(MySqlRequest $request) : void{
			$rows = (array) $request->getQuery()->getResult()->getRows();
			$xuids = [];
			foreach($rows as $member){
				$xuids[] = $member["xuid"];
			}
			Core::getInstance()->getUserPool()->useUsers($xuids, function(array $users) use($rows) : void{
				foreach($rows as $member){
					$this->addMember(new GangMember($this->getGang(), $users[$member["xuid"]], max(0, min(GangMember::ROLE_LEADER, $member["role"])), $member["kills"], $member["deaths"], $member["blocks"], $member["joined"]));
				}
				$this->setLoaded();
				$this->getGang()->tryLoadedClosure();
			});
		});
	}

	/**
	 * For Gang member status in player session
	 */
	public function getMember(Player|User $player) : ?GangMember{
		return $this->members[$player->getXuid()] ?? null;
	}

	public function getMemberByXuid(int $xuid) : ?GangMember{
		return $this->members[$xuid] ?? null;
	}

	public function getMemberByName(string $gamertag) : ?GangMember{
		foreach($this->members as $member){
			if(strtolower($member->getUser()->getGamertag()) === strtolower($gamertag)) return $member;
		}
		return null;
	}

	public function getLeader() : ?GangMember{
		foreach($this->getMembers() as $member){
			if($member->getRole() === GangMember::ROLE_LEADER) return $member;
		}
		return null;
	}

	/** @return GangMember[] */
	public function getMembers(int $role = -1) : array{
		if($role == -1)
			return $this->members;

		$members = [];
		foreach($this->members as $member){
			if($member->getRole() == $role)
				$members[$member->getXuid()] = $member;
		}
		return $members;
	}

	public function getMembersBelow(int $highestrole = -1) : array{
		if($highestrole == -1)
			return $this->members;

		$members = [];
		foreach($this->members as $member){
			if($member->getRole() <= $highestrole)
				$members[$member->getXuid()] = $member;
		}
		return $members;
	}


	public function addMember(GangMember $member, bool $sync = false) : void{
		$this->members[$member->getXuid()] = $member;
		if($sync) $member->sync(true);
	}

	public function removeMember(int $xuid, bool $leave = true, bool $sync = false) : bool{
		$member = $this->getMemberByXuid($xuid);
		if($member == null) return false;

		if($leave) $member->leave($sync);
		unset($this->members[$xuid]);
		return true;
	}

	public function isMember(Player|User|GangMember $player) : bool{
		foreach($this->getMembers() as $member){
			if($member->getXuid() == $player->getXuid()){
				return true;
			}
		}
		return false;
	}

	public function updateMember(GangMember $m) : bool{
		foreach($this->members as $xuid => $member){
			if($xuid == $m->getXuid()){
				$this->members[$xuid] = $m;
				return true;
			}
		}
		return false;
	}

	public function getOnlineMembers(bool $checkSub = false) : array{
		$members = [];
		foreach($this->getMembers() as $member){
			if($member->isOnline($checkSub)) $members[] = $member;
		}
		return $members;
	}

	public function getOnlineCount() : int{
		return count($this->getOnlineMembers());
	}

	public function getRoleName(int $role) : string{
		switch($role){
			case GangMember::ROLE_MEMBER:
				return "Member";
			case GangMember::ROLE_ELDER:
				return "Elder";
			case GangMember::ROLE_CO_LEADER:
				return "Co-Leader";
			case GangMember::ROLE_LEADER:
				return "Leader";
			default:
				return "idk";
		}
	}

	public function getRoleAbove(int $role) : int{
		switch($role){
			case GangMember::ROLE_MEMBER:
				return GangMember::ROLE_ELDER;
			case GangMember::ROLE_ELDER:
				return GangMember::ROLE_CO_LEADER;
			case GangMember::ROLE_CO_LEADER:
				return GangMember::ROLE_LEADER;
			case GangMember::ROLE_LEADER:
			default:
				return -1;
		}
	}

	public function getRoleBelow(int $role) : int{
		switch($role){
			default:
			case GangMember::ROLE_MEMBER:
				return -1;
			case GangMember::ROLE_ELDER:
				return GangMember::ROLE_MEMBER;
			case GangMember::ROLE_CO_LEADER:
				return GangMember::ROLE_ELDER;
			case GangMember::ROLE_LEADER:
				return GangMember::ROLE_CO_LEADER;
		}
	}

	public function getOrderedMemberList() : string{
		$members = [
			GangMember::ROLE_LEADER => [],
			GangMember::ROLE_ELDER => [],
			GangMember::ROLE_MEMBER => []
		];
		foreach($this->getMembers() as $member){
			$members[$member->getRole()][$member->getXuid()] = $member;
		}
		$string = "";
		foreach($members as $key => $category){
			foreach($category as $xuid => $member){
				$string .= "- " . $member->getName() . " (" . $this->getRoleName($key) . ")" . PHP_EOL;
			}
		}
		return $string;
	}

	public function saveSlow() : void{
		foreach($this->getMembers() as $member)
			$member->save();
	}

	/**
	 * Saves all gang members at once;
	 * More resource efficient than separately
	 */
	public function save(bool $async = true) : void{
		if($async){
			$request = new MySqlRequest("save_gang_members_" . $this->getGang()->getId(), []);
			foreach($this->getMembers() as $member){
				$request->addQuery(new MySqlQuery(
					"main",
					"INSERT INTO gang_members(xuid, gangid, role, kills, deaths, blocks, joined) VALUES(?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE role=VALUES(role), kills=VALUES(kills), deaths=VALUES(deaths), blocks=VALUES(blocks)",
					[
						$member->getXuid(),
						$this->getGang()->getId(),
						$member->getRole(),
						$member->getKills(),
						$member->getDeaths(),
						$member->getBlocks(),
						$member->getJoined()
					]
				));
			}
			Prison::getInstance()->getSessionManager()->sendStrayRequest($request, function(MySqlRequest $request) : void{});
		}else{
			$db = Prison::getInstance()->getSessionManager()->getDatabase();
			$stmt = $db->prepare("INSERT INTO gang_members(xuid, gangid, role, kills, deaths, blocks, joined) VALUES(?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE role=VALUES(role), kills=VALUES(kills), deaths=VALUES(deaths), blocks=VALUES(blocks)");

			$id = $this->getGang()->getId();

			foreach($this->getMembers() as $member){
				$xuid = $member->getXuid();
				$role = $member->getRole();
				$kills = $member->getKills();
				$deaths = $member->getDeaths();
				$blocks = $member->getBlocks();
				$joined = $member->getJoined();

				$stmt->bind_param("iiiiiii", $xuid, $id, $role, $kills, $deaths, $blocks, $joined);
				$stmt->execute();
			}
			$stmt->close();	
		}
	}

}