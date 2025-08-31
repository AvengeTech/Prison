<?php namespace prison\gangs\objects;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\GangManager;
use prison\gangs\battle\{
	Battle,
	BattleRequestManager,
	BattleStatManager
};

use core\Core;
use core\chat\Chat;
use core\network\protocol\ServerSubUpdatePacket;
use core\session\mysqli\data\{
	MySqlQuery,
	MySqlRequest
};
use core\user\User;
use core\utils\TextFormat;

class Gang{

	const SYNC_MEMBER = 0; //kills/deaths/blocks
	const SYNC_MEMBER_CHANGE = 1; //add/remove members
	const SYNC_ALLIANCE = 2;
	const SYNC_ALLIANCE_INVITE = 3;
	const SYNC_BATTLE_REQUEST = 4;
	const SYNC_BATTLE_STATS = 5;
	const SYNC_INVITE = 6;
	const SYNC_GANG_DATA = 7; //name/desc/level/trophies/bank
	const SYNC_GANG_DELETE = 8;

	const MAX_MEMBERS = 7;
	const MAX_LEVEL = 5;
	const MAX_ALLIANCES = 3;

	/**
	 * level -> [trophies, techits]
	 */
	const LEVEL_CHART = [
		1 => ["trophies" => 500, "techits" => 500000],
		2 => ["trophies" => 1000, "techits" => 1000000],
		3 => ["trophies" => 1500, "techits" => 2000000],
		4 => ["trophies" => 2000, "techits" => 5000000],
		5 => ["trophies" => 3000, "techits" => 10000000],
	];

	const COLOR_ROLE = [
		-1 => TextFormat::WHITE,
		GangMember::ROLE_MEMBER => TextFormat::GREEN,
		GangMember::ROLE_ELDER => TextFormat::DARK_GREEN,
		GangMember::ROLE_CO_LEADER => TextFormat::RED,
		GangMember::ROLE_LEADER => TextFormat::YELLOW
	];
	
	public ?User $leader = null;

	public string $name = "My Gang";
	public string $description = "Join my gang!";

	public int $level = 0;

	public int $trophies = 0;
	public int $kills = 0;
	public int $deaths = 0;
	public int $blocks = 0;

	public array $klog = [];
	public array $dlog = [];
	public int $bblog = 0;

	public int $bank = 0;

	public int $created = 0;

	public BattleStatManager $battleStatManager;
	public BattleRequestManager $battleRequestManager;

	public MemberManager $memberManager;
	public InviteManager $inviteManager;
	public AllianceInviteManager $allianceInviteManager;
	
	public bool $loaded = false;
	public ?\Closure $loadClosure = null;

	public function __construct(public int $id){
		$this->battleStatManager = new BattleStatManager($this);
		$this->battleRequestManager = new BattleRequestManager($this);

		$this->memberManager = new MemberManager($this);
		$this->inviteManager = new InviteManager($this);
		$this->allianceInviteManager = new AllianceInviteManager($this);
	}
	
	public function isLoaded() : bool{
		return $this->loaded;
	}
	
	public function setLoaded(bool $loaded = true) : void{
		$this->loaded = $loaded;
	}
	
	public function getLoadClosure() : ?\Closure{
		return $this->loadClosure;
	}
	
	public function tryLoadedClosure() : bool{
		if(
			$this->isLoaded() &&
			$this->getBattleStatManager()->isLoaded() &&
			$this->getMemberManager()->isLoaded() &&
			$this->getAllianceManager()->isLoaded($this)
		){
			if(($closure = $this->getLoadClosure()) !== null){
				$closure($this);
				$this->loadClosure = null;
			}
			return true;
		}
		return false;
	}

	public function load(?\Closure $closure = null) : void{
		$this->loadClosure = $closure;

		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("load_gang_" . $this->getId(), new MySqlQuery(
			"main",
			"SELECT * FROM gang_base_data WHERE id=?",
			[$this->getId()]
		)), function(MySqlRequest $request) : void{
			$result = $request->getQuery()->getResult()->getRows();
			if(count($result) > 0){
				$data = array_shift($result);
				Core::getInstance()->getUserPool()->useUser($data["leader"], function(User $user) use($data) : void{
					$this->leader = $user;

					$this->name = $data["name"];
					$this->description = $data["description"];

					$this->level = $data["level"];
					$this->trophies = $data["trophies"];

					$this->kills = $data["kills"];
					$this->deaths = $data["deaths"];
					$this->blocks = $data["blocks"];
					$this->bank = $data["bank"];

					$this->created = $data["created"];

					$this->setLoaded();
					$this->tryLoadedClosure();

					$this->fullSyncRequest();
				});
			}
		});

		$this->getBattleStatManager()->load();
		$this->getMemberManager()->load();
		$this->getGangManager()->getAllianceManager()->load($this->getId());
	}

	public function tick() : void{
		$this->getBattleRequestManager()->tick();
		$this->getInviteManager()->tick();
		$this->getAllianceInviteManager()->tick();
	}

	public function getGangManager() : GangManager{
		return Prison::getInstance()->getGangs()->getGangManager();
	}

	public function getAllianceManager() : AllianceManager{
		return $this->getGangManager()->getAllianceManager();
	}

	public function getBattleRequestManager() : BattleRequestManager{
		return $this->battleRequestManager;
	}

	public function inBattle() : bool{
		return $this->getGangManager()->getBattleManager()->inBattle($this);
	}

	public function getBattle() : ?Battle{
		return $this->getGangManager()->getBattleManager()->getBattleByGang($this);
	}

	public function getId() : int{
		return $this->id;
	}

	public function isLeader(Player|User $player) : bool{
		/** @var PrisonPlayer $player */
		if(!$this->inGang($player)) return false;
		return $player->getXuid() == $this->getLeader()->getXuid() || ($player instanceof PrisonPlayer && $player->isTier3());
	}

	public function setLeader(Player|User $player) : bool {
		/** @var PrisonPlayer $player */
		if(!$this->inGang($player))
			return false;

		if($this->getLeader() !== null)
			$this->getMemberManager()->getMemberByXuid($this->getLeader()->getXuid())->setRole(GangMember::ROLE_CO_LEADER);

		$this->getMemberManager()->getMemberByXuid(($xuid = $player->getXuid()))->setRole(GangMember::ROLE_LEADER);
		$this->leader = $player instanceof User ? $player : $player->getUser();
		return true;
	}

	public function getLeader() : ?User{
		return $this->leader;
	}

	public function getLeaderMember() : ?GangMember{
		return $this->getMemberManager()->getLeader();
	}

	public function getLeaderXuid() : int{
		if($this->getLeader() == null) return 0;
		return $this->getLeader()->getXuid();
	}

	public function getName() : string{
		return $this->name;
	}

	public function setName(string $name, bool $sync = true) : bool{
		if($name == $this->getName()) return false;
		$this->name = $name;
		if($sync) $this->syncSend("name");
		return true;
	}

	public function getDescription() : string{
		return $this->description;
	}

	public function setDescription(string $message, bool $sync = true) : bool{
		if($message == $this->getDescription()) return false;
		$this->description = $message;
		if($sync) $this->syncSend("description");
		return true;
	}

	public function getLevel() : int{
		return $this->level;
	}

	public function canLevelUp() : bool{
		if(($level = $this->getLevel()) >= self::MAX_LEVEL)
			return false;

		$ld = self::LEVEL_CHART[min(max(1, $level + 1), 5)];
		return $this->getTrophies() >= $ld["trophies"] && $this->getBankValue() >= $ld["techits"];
	}

	public function levelUp() : bool{
		if($this->getLevel() >= 5)
			return false;

		$this->level++;
		$level = $this->getLevel();
		$this->takeTrophies(self::LEVEL_CHART[$level]["trophies"], false);
		$this->takeFromBank(self::LEVEL_CHART[$level]["techits"], null, false);
		$this->syncSend(["level", "bank", "trophies"]);
		return true;
	}

	public function getTrophies() : int{
		return $this->trophies;
	}

	public function setTrophies(int $trophies, bool $sync = true) : void{
		$this->trophies = $trophies;
		if($sync) $this->syncSend("trophies");
	}

	public function addTrophies(int $trophies, bool $sync = true) : void{
		$this->setTrophies($this->getTrophies() + $trophies, $sync);
	}

	public function takeTrophies(int $trophies, bool $sync = true) : bool{
		if($this->getTrophies() <= 0) return false;

		if($this->getTrophies() - $trophies < 0){
			$this->trophies = 0;
			if($sync) $this->syncSend("trophies");
			return true;
		}
		$this->addTrophies(-$trophies, $sync);
		return true;
	}

	public function getKills() : int{
		return $this->kills;
	}

	public function addKill(int $amt = 1, ?Player $player = null) : bool{
		$this->kills += $amt;
		if($player !== null){
			if(isset($this->klog[$player->getName()])){
				if(time() < $this->klog[$player->getName()]) return false;
			}

			$chance = TrophyData::PERCENT_KILLS;
			foreach($this->getMemberManager()->getMembers() as $m){
				$chance += TrophyData::PERCENT_KILLS_MEMBER;
			}
			if(mt_rand(1, 100) <= $chance){
				if(
					!($gm = $this->getGangManager())->inGang($player) ||
					(
						$this->getId() != ($pg = $gm->getPlayerGang($player))->getId() &&
						!$this->getAllianceManager()->areAllies($this, $pg)
					)
				){
					$this->addTrophies(TrophyData::EVENT_KILL);
					$this->klog[$player->getName()] = time() + (60 * 30);
					return true;
				}
			}
		}
		return false;
	}

	public function getDeaths() : int{
		return $this->deaths;
	}

	public function addDeath(int $amt = 1, ?Player $hit = null, ?Player $killer = null) : bool{
		$this->deaths += $amt;
		if($hit !== null && $killer !== null){
			if(!isset($this->dlog[$hit->getName()])){
				$this->dlog[$hit->getName()] = [];
			}
			if(!isset($this->dlog[$hit->getName()][$killer->getName()])){
				$this->dlog[$hit->getName()][$killer->getName()] = 1;
			}else{
				if($this->dlog[$hit->getName()][$killer->getName()] < 3)
					return false;

				$this->dlog[$hit->getName()][$killer->getName()]++;
			}
			if(
				!($gm = $this->getGangManager())->inGang($killer) ||
				(
					$this->getId() != ($kg = $gm->getPlayerGang($killer))->getId() &&
					!$this->getAllianceManager()->areAllies($this, $kg)
				)
			){
				$this->takeTrophies(TrophyData::EVENT_DEATH);
				return true;
			}
		}
		return false;
	}

	public function getBlocks() : int{
		return $this->blocks;
	}

	public function addBlock(int $amt = 1) : bool{
		$this->blocks += $amt;

		if($this->getBlocks() % 500 == 0){
			if($this->bblog < TrophyData::MAX_BLOCK_BREAK){
				if(mt_rand(1, 100) <= (25 + ($this->getLevel() * TrophyData::PERCENT_BLOCK_BREAK_LEVEL))){
					$this->addTrophies(TrophyData::EVENT_BLOCK_BREAK);
					$this->bblog += TrophyData::EVENT_BLOCK_BREAK;
					return true;
				}
			}
		}
		return false;
	}

	public function getBankValue() : int{
		return $this->bank;
	}

	public function setBankValue(int $value, bool $sync = true) : void{
		$this->bank = $value;
		if($sync) $this->syncSend("bank");
	}

	public function addToBank(int $amount, ?Player $player = null, bool $sync = true) : void {
		/** @var PrisonPlayer $player */
		$this->setBankValue($this->getBankValue() + $amount, $sync);
		if($player instanceof Player){
			$player->takeTechits($amount);
		}
	}

	public function takeFromBank(int $amount, ?Player $player = null, bool $sync = true) : void {
		/** @var PrisonPlayer $player */
		$this->setBankValue($this->getBankValue() - $amount, $sync);
		if($player instanceof Player){
			$player->addTechits($amount);
		}
	}

	public function getCreated() : int{
		return $this->created;
	}

	public function getCreatedFormatted() : string{
		return date("m/d/y", $this->created);
	}

	public function getBattleStatManager() : BattleStatManager{
		return $this->battleStatManager;
	}

	public function getMemberManager() : MemberManager{
		return $this->memberManager;
	}

	public function inGang(Player|User|GangMember $player) : bool{
		return $this->getMemberManager()->isMember($player);
	}

	public function getMember(Player|User $player) : ?GangMember{
		return $this->getMemberManager()->getMember($player);
	}

	public function getMemberByName(string $gamertag) : ?GangMember{
		return $this->getMemberManager()->getMemberByName($gamertag);
	}

	public function isElder(Player $player) : bool{
		return $this->getRole($player) == GangMember::ROLE_ELDER;
	}

	public function getRole(Player $player) : int{
		/** @var PrisonPlayer $player */
		if(!$this->inGang($player)) return -1;
		if ($player->isTier3()) return GangMember::ROLE_LEADER;
		return $this->getMemberManager()->getMember($player)->getRole();
	}

	public function getMaxMembers() : int{
		return min($this->getLevel(), 4) + 4;
	}

	public function sendMessage(Player|User $player, string $message, int $type = 1, bool $send = true) : bool {
		/** @var PrisonPlayer|User $player */
		$pg = $this->getGangManager()->getPlayerGang($player);
		if($pg == null) return false;
		$pm = $pg->getMemberManager()->getMember($player);

		$mm = $this->getMemberManager();

		foreach($mm->getOnlineMembers() as $member){
			$member->getPlayer()->sendMessage(($type == 1 ? TextFormat::DARK_GREEN : TextFormat::AQUA) . TextFormat::BOLD . "[" . $pg->getName() . "] " . TextFormat::RESET . self::COLOR_ROLE[$pm->getRole()] . $pm->getUser()->getGamertag() . ": " . TextFormat::GRAY . ($msg = (($player instanceof Player && $player->hasRank()) ? Chat::convertWithEmojis($message) : $message)));
		}

		if($send){
			$servers = [];
			foreach(Core::thisServer()->getSubServers(false, true) as $server){
				$servers[] = $server->getIdentifier();
			}
			(new ServerSubUpdatePacket([
				"server" => $servers,
				"type" => "gangChat",
				"data" => [
					"gang" => $this->getId(),
					"player" => $player->getName(),
					"message" => $message,
					"msgType" => $type,
				]
			]))->queue();
		}
		return true;
	}

	public function getInviteManager() : InviteManager{
		return $this->inviteManager;
	}

	public function getAllianceInviteManager() : AllianceInviteManager{
		return $this->allianceInviteManager;
	}

	public function getAlliances() : array{
		return $this->getGangManager()->getAllianceManager()->getAlliances($this->getId());
	}

	/**
	 * Requests data to sync gang to other subservers
	 * 
	 * (Will also load gang if necessary)
	 */
	public function fullSyncRequest() : void{
		$servers = [];
		foreach(Core::thisServer()->getSubServers(false, true) as $server){
			$servers[] = $server->getIdentifier();
		}
		(new ServerSubUpdatePacket([
			"server" => $servers,
			"type" => "gangFullSyncRequest",
			"data" => [
				"gang" => $this->getId(),
			]
		]))->queue();

		echo "requested full sync", PHP_EOL;
	}

	public function fullSyncSend(string $server = "all") : void{
		//send ALL gang data to server newly loading gang
		//incase it has been changed recently
		
		if(!$this->isLoaded()) return;

		$this->syncSend(["name", "description", "level", "trophies", "bank"], $server);
		foreach($this->getMemberManager()->getMembers() as $member){
			if($member->hasChanged()) $member->sync(false, $server);
		}
	}

	public function syncSend(string|array $type, string $server = "all"){
		$toAdd = [];
		if(is_string($type)){
			$toAdd[$type] = match($type){
				"name" => $this->getName(),
				"description" => $this->getDescription(),
				"level" => $this->getLevel(),
				"trophies" => $this->getTrophies(),
				"bank" => $this->getBankValue(),
				default => -1
			};
		}else{
			foreach($type as $t){
				$toAdd[$t] = match($t){
					"name" => $this->getName(),
					"description" => $this->getDescription(),
					"level" => $this->getLevel(),
					"trophies" => $this->getTrophies(),
					"bank" => $this->getBankValue(),
					default => -1
				};
			}
		}
		if($server === "all"){
			$server = [];
			foreach(Core::thisServer()->getSubServers(false, true) as $serv){
				$server[] = $serv->getIdentifier();
			}
		}
		$pkData = [
			"server" => $server,
			"type" => "gangSync",
			"data" => [
				"type" => Gang::SYNC_GANG_DATA,
				"gang" => $this->getId(),
			]
		];
		foreach($toAdd as $k => $v){
			$pkData["data"][$k] = $v;
		}
		(new ServerSubUpdatePacket($pkData))->queue();

		echo "sent gang data to sync:", PHP_EOL;
		var_dump($toAdd);
	}

	public function selfDestruct(bool $send = false) : void{
		foreach($this->getMemberManager()->getMembers() as $member){
			$member->leave();
			if($member->isOnline())
				$member->getPlayer()->sendMessage(TextFormat::RI . "Your gang leader has deleted your gang!");

		}
		foreach($this->getAlliances() as $ally){
			$ally->terminate();
		}

		$this->getBattleStatManager()->delete();

		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("delete_gang_battlestats_" . $this->getId(),
			new MySqlQuery(
				"main",
				"DELETE FROM gang_base_data WHERE id=?",
				[$this->getId()]
			)
		), function(MySqlRequest $request) : void{});

		unset($this->getGangManager()->gangs[$this->getId()]);

		if($send){
			$servers = [];
			foreach(Core::thisServer()->getSubServers(false, true) as $server){
				$servers[] = $server->getIdentifier();
			}
			(new ServerSubUpdatePacket([
				"server" => $servers,
				"type" => "gangSync",
				"data" => [
					"type" => Gang::SYNC_GANG_DELETE,
					"gang" => $this->getId(),
				]
			]))->queue();
		}
	}

	public function save(bool $async = true) : void{
		if(!$this->isLoaded()) return;
		if($async){
			Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("save_gang_" . $this->getId(), new MySqlQuery(
				"main",
				"INSERT INTO gang_base_data(
					id,
					leader,
					name,
					description,
					level,
					trophies,
					kills,
					deaths,
					blocks,
					bank,
					created
				) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
				ON DUPLICATE KEY UPDATE 
					leader=VALUES(leader),
					name=VALUES(name),
					description=VALUES(description),
					level=VALUES(level),
					trophies=VALUES(trophies),
					kills=VALUES(kills),
					deaths=VALUES(deaths),
					blocks=VALUES(blocks),
					bank=VALUES(bank)",
				[
					$this->getId(),
					$this->getLeader()->getXuid(),
					$this->getName(),
					$this->getDescription(),
					$this->getLevel(),
					$this->getTrophies(),
					$this->getKills(),
					$this->getDeaths(),
					$this->getBlocks(),
					$this->getBankValue(),
					$this->getCreated()
				]
			)), function(MySqlRequest $request) : void{});
		}else{
			$id = $this->getId();
			$leader = $this->getLeaderXuid();

			$name = $this->getName();
			$description = $this->getDescription();

			$level = $this->getLevel();

			$trophies = $this->getTrophies();
			$kills = $this->getKills();
			$deaths = $this->getDeaths();
			$blocks = $this->getBlocks();

			$bank = $this->getBankValue();

			$created = $this->getCreated();

			$db = Prison::getInstance()->getSessionManager()->getDatabase();
			$stmt = $db->prepare(
				"INSERT INTO gang_base_data(
					id,
					leader,
					name,
					description,
					level,
					trophies,
					kills,
					deaths,
					blocks,
					bank,
					created
				) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
				ON DUPLICATE KEY UPDATE 
					leader=VALUES(leader),
					name=VALUES(name),
					description=VALUES(description),
					level=VALUES(level),
					trophies=VALUES(trophies),
					kills=VALUES(kills),
					deaths=VALUES(deaths),
					blocks=VALUES(blocks),
					bank=VALUES(bank)"
			);
			$stmt->bind_param("iissiiiiiii", $id, $leader, $name, $description, $level, $trophies, $kills, $deaths, $blocks, $bank, $created);
			$stmt->execute();
			$stmt->close();	
		}

		$this->getBattleStatManager()->save($async);
		$this->getMemberManager()->save($async);

		//$this->getGangManager()->getAllianceManager()->save($id);
	}

}