<?php namespace prison\gangs\objects;

use pocketmine\player\Player;

use prison\Prison;

use core\Core;
use core\network\protocol\ServerSubUpdatePacket;
use core\session\mysqli\data\{
	MySqlQuery,
	MySqlRequest
};
use core\session\stray\StrayRequest;
use core\user\User;

class GangMember{

	const SYNC_JOIN = 0;
	const SYNC_LEAVE = 1;

	const ROLE_MEMBER = 0;
	const ROLE_ELDER = 1;
	const ROLE_CO_LEADER = 4;
	const ROLE_LEADER = 5;

	const CHAT_ALL = 0;
	const CHAT_GANG = 1;
	const CHAT_ALLY = 2;

	public int $chatMode = self::CHAT_ALL;
	//TODO: Allow specified alliance chats
	public int $chatAlly = -1;

	public bool $changed = false;

	public function __construct(
		public Gang $gang,
		public User $user,
		public int $role = self::ROLE_MEMBER,
		
		public int $kills = 0,
		public int $deaths = 0,
		public int $blocks = 0,
		
		public int $joined = 0
	){
		$this->role = max(self::ROLE_MEMBER, min(self::ROLE_LEADER, $this->role));
	}

	public function getUser() : User{
		return $this->user;
	}

	public function getPlayer() : ?Player{
		return $this->getUser()->getPlayer();
	}

	public function getName() : string{
		return $this->getUser()->getGamertag();
	}

	public function getXuid() : int{
		return $this->getUser()->getXuid();
	}

	public function isOnline(bool $checkSub = false) : bool{
		$pl = $this->getPlayer() !== null;
		if($pl) return true;
		if(!$checkSub){
			return $pl;
		}

		//might be slow idk
		foreach(Core::thisServer()->getSubServers(false, true) as $server){
			foreach($server->getCluster()->getPlayers() as $pl){
				if($pl->getUser()->getName() === $this->getName()) return true;
			}
		}
		return false;
	}

	public function getGang() : Gang{
		return $this->gang;
	}

	public function getMemberManager() : MemberManager{
		return $this->getGang()->getMemberManager();
	}

	public function getRole() : int{
		return $this->role;
	}

	public function setRole(int $role, bool $sync = false) : void{
		$this->role = $role;
		$this->setChanged();
		if($sync) $this->sync();
	}

	public function getKills() : int{
		return $this->kills;
	}

	public function addKill(int $amt = 1) : void{
		$this->kills += $amt;
		$this->setChanged();
	}

	public function getDeaths() : int{
		return $this->deaths;
	}

	public function addDeath(int $amt = 1) : void{
		$this->deaths += $amt;
		$this->setChanged();
	}

	public function getBlocks() : int{
		return $this->blocks;
	}

	public function addBlock(int $amt = 1) : void{
		$this->blocks += $amt;
		$this->setChanged();
	}

	public function getJoined() : int{
		return $this->joined;
	}

	public function hasChanged() : bool{
		return $this->changed;
	}

	public function setChanged(bool $changed = true) : void{
		$this->changed = $changed;
	}

	public function sync(bool $add = false, string $server = "all") : void{
		$servers = [];
		if($server === "all"){
			foreach(Core::thisServer()->getSubServers(false, true) as $server){
				$servers[] = $server->getIdentifier();
			}
		}else $servers = $server;

		$data = [
			"server" => $servers,
			"type" => "gangSync",
			"data" => [
				"gang" => $this->getGang()->getId(),
				"type" => ($add ? Gang::SYNC_MEMBER_CHANGE : Gang::SYNC_MEMBER),
				"player" => $this->getUser()->getGamertag(),
				"role" => $this->getRole(),
				"kills" => $this->getKills(),
				"deaths" => $this->getDeaths(),
				"blocks" => $this->getBlocks(),
				"joined" => $this->getJoined(),
			]
		];
		if($add) $data["data"]["change"] = GangMember::SYNC_JOIN;
		(new ServerSubUpdatePacket($data))->queue();
	}

	final public function leave(bool $sync = false) : void{
		Prison::getInstance()->getGangs()->getGangManager()->addLeftGang($this->getUser());

		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("load_gang_members_" . $this->getGang()->getId(),
			new MySqlQuery(
				"main",
				"DELETE FROM gang_members WHERE xuid=?",
				[$this->getXuid()]
			)
		), function(MySqlRequest $request) : void{});

		if($sync){
			$servers = [];
			foreach(Core::thisServer()->getSubServers(false, true) as $server){
				$servers[] = $server->getIdentifier();
			}
			(new ServerSubUpdatePacket([
				"server" => $servers,
				"type" => "gangSync",
				"data" => [
					"gang" => $this->getGang()->getId(),
					"type" => Gang::SYNC_MEMBER_CHANGE,
					"change" => self::SYNC_LEAVE,
					"player" => $this->getUser()->getGamertag(),
				]
			]))->queue();
		}
	}

	public function save(bool $async = true) : void{
		if($async){
			Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("save_gang_member_" . $this->getXuid(),
				new MySqlQuery(
					"main",
					"INSERT INTO gang_members(xuid, gangid, role, kills, deaths, blocks, joined) VALUES(?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE role=VALUES(role), kills=VALUES(kills), deaths=VALUES(deaths), blocks=VALUES(blocks)",
					[
						$this->getXuid(),
						$this->getGang()->getId(),
						$this->getRole(),
						$this->getKills(),
						$this->getDeaths(),
						$this->getBlocks(),
						$this->getJoined()
					]
				)
			), function(StrayRequest $request) : void{
				$this->setChanged(false);
			});
		}else{
			$xuid = $this->getXuid();
			$id = $this->getGang()->getId();
			$role = $this->getRole();
			$kills = $this->getKills();
			$deaths = $this->getDeaths();
			$blocks = $this->getBlocks();

			$joined = $this->getJoined();

			$db = Prison::getInstance()->getSessionManager()->getDatabase();
			$stmt = $db->prepare("INSERT INTO gang_members(xuid, gangid, role, kills, deaths, blocks, joined) VALUES(?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE role=VALUES(role), kills=VALUES(kills), deaths=VALUES(deaths), blocks=VALUES(blocks)");
			$stmt->bind_param("iiiiiii", $xuid, $id, $role, $kills, $deaths, $blocks, $joined);
			$stmt->execute();
			$stmt->close();

			$this->setChanged(false);
		}
	}

	public function getChatMode() : int{
		return $this->chatMode;
	}

	public function setChatMode(int $mode = self::CHAT_ALL) : void{
		$this->chatMode = $mode;
	}

}