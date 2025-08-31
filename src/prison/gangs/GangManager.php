<?php namespace prison\gangs;

use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

use prison\Prison;
use prison\gangs\battle\BattleManager;
use prison\gangs\objects\{
	Gang,
	GangMember,
	GangInvite,
	AllianceManager
};
use prison\PrisonPlayer;
use prison\gangs\shop\GangShop;

use core\Core;
use core\network\protocol\ServerSubUpdatePacket;
use core\session\mysqli\data\{
	MySqlQuery,
	MySqlRequest
};
use core\user\User;

class GangManager{

	const GANG_PRICE = 100000;

	public array $gangs = [];

	public AllianceManager $allianceManager;
	public BattleManager $battleManager;
	public GangShop $gangShop;

	public array $left = [];

	public function __construct(public Gangs $main){
		$this->allianceManager = new AllianceManager($this);
		$this->battleManager = new BattleManager($this);
		$this->gangShop = new GangShop($this);

		Prison::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() : void{
			$this->sendGangRequest();
		}), 10);
	}

	public function close() : void{
		$this->saveAll();
		$this->getAllianceManager()->saveAll();
		$this->getBattleManager()->cancelAllBattles("Server restarting!");
	}

	/**
	 * Request to load gangs from other subservers on startup
	 */
	public function sendGangRequest() : void{
		$servers = [];
		foreach(Core::thisServer()->getSubServers(false, true) as $server){
			$servers[] = $server->getIdentifier();
		}
		(new ServerSubUpdatePacket([
			"server" => $servers,
			"type" => "gangRequest"
		]))->queue();
	}

	public function unloadInactiveGangs() : void{
		//TODO
	}

	public function getMain() : Gangs{
		return $this->main;
	}

	public function getAllianceManager() : AllianceManager{
		return $this->allianceManager;
	}

	public function getBattleManager() : BattleManager{
		return $this->battleManager;
	}

	public function getGangShop() : GangShop{
		return $this->gangShop;
	}

	/** @return Gang[] */
	public function getGangs() : array{
		return $this->gangs;
	}

	public function tick() : void{
		foreach($this->getGangs() as $gang)
			$gang->tick();

		$this->getBattleManager()->tick();
	}

	public function getGangById(int $id) : ?Gang{
		return $this->gangs[$id] ?? null;
	}

	public function getGangByGang(Gang $gang) : ?Gang{
		return $this->getGangById($gang->getId());
	}

	public function getGangByName(string $name) : ?Gang{
		foreach($this->getGangs() as $gang){
			if(strtolower($gang->getName()) == strtolower($name)) return $gang;
		}
		return null;
	}

	public function saveAll(bool $async = false) : void{
		foreach($this->getGangs() as $gang)
			$gang->save($async);
	}

	public function getPlayerGang(Player|User $player) : ?Gang{
		foreach($this->getGangs() as $gang){
			if($gang->inGang($player))
				return $gang;
		}
		return null;
	}

	public function getGangByPlayerXuid(string|int $xuid): ?Gang {
		foreach ($this->getGangs() as $gang) {
			foreach ($gang->getMemberManager()->getMembers() as $member) {
				if ($member->getXuid() == $xuid) return $gang;
			}
		}
		return null;
	}
	
	public function getGangByPlayerName(string $name): ?Gang {
		foreach ($this->getGangs() as $gang) {
			foreach ($gang->getMemberManager()->getMembers() as $member) {
				if (strtolower($member->getName()) === strtolower($name)) return $gang;
			}
		}
		return null;
	}


	public function inGang(Player|User $player) : bool{
		foreach($this->getGangs() as $gang){
			if($gang->inGang($player)){
				return true;
			}
		}
		return false;
	}

	public function createGang(Player $player, string $name = "My Gang", string $description = "Join my gang!") : void{
		/** @var PrisonPlayer $player */
		if($this->inGang($player)) return;

		$this->getNewGangId(function(int $id) use($player, $name, $description) : void{
			if(!$player->isConnected()) return;
			if($this->inGang($player)) return;
			
			$gang = new Gang($id);
			$member = new GangMember($gang, $player->getUser(), GangMember::ROLE_LEADER);
			$gang->getMemberManager()->addMember($member);
			$gang->setLeader($player);
			$gang->setName($name);
			$gang->setDescription($description);

			$this->gangs[$gang->getId()] = $gang;
			$gang->setLoaded();
			$gang->save();
		});
	}

	public function doesGangNameExist(string $name, \Closure $closure) : void{
		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("check_gang_exists_" . $name,
			new MySqlQuery(
				"main",
				"SELECT EXISTS(SELECT * FROM gang_base_data WHERE name=?)",
				[$name]
			)
		), function(MySqlRequest $request) use($closure) : void{
			$result = (array) $request->getQuery()->getResult()->getRows()[0];
			$exists = (bool) array_shift($result);
			$closure($exists);
		});
	}

	public function getNewGangId(\Closure $closure) : void{
		$id = mt_rand(1000000000, 9999999999);
		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("check_gangid_exists_" . $id,
			new MySqlQuery(
				"main",
				"SELECT id FROM gang_base_data WHERE id=?",
				[$id]
			)
		), function(MySqlRequest $request) use($id, $closure) : void{
			$result = (array) $request->getQuery()->getResult()->getRows();
			if(count($result) > 0){
				$this->getNewGangId($closure);
			}else{
				$closure($id);
			}
		});
	}

	public function isLoaded(Gang|int $gang) : bool{
		$id = ($gang instanceof Gang ? $gang->getId() : $gang);
		return isset($this->gangs[$id]);
	}

	public function loadGang(int $gangid, ?\Closure $closure = null) : bool{
		if($this->isLoaded($gangid)) return false;

		$this->gangs[$gangid] = new Gang($gangid);
		$this->gangs[$gangid]->load($closure);
		return true;
	}

	public function unloadGang(int $gangid, bool $save = true, bool $async = true) : bool{
		if(($gang = $this->getGangById($gangid)) == null)
			return false;

		if($gang->getMemberManager()->getOnlineCount() > 1)
			return false;

		foreach($gang->getAllianceInviteManager()->getInvites() as $inv)
			$inv->decline();

		if($save) $gang->save($async);

		unset($this->gangs[$gangid]);
		return true;
	}

	public function saveGang(int $gangid, bool $checkPlayers = true, bool $async = true) : void{
		if(($gang = $this->getGangById($gangid)) == null)
			return;

		if($checkPlayers && $gang->getMemberManager()->getOnlineCount() > 1)
			return;

		$gang->save($async);
	}

	public function loadByName(string $name, ?\Closure $closure = null) : void{
		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("load_gang_byname_" . $name,
			new MySqlQuery(
				"main",
				"SELECT id FROM gang_base_data WHERE name=?",
				[$name]
			)
		), function(MySqlRequest $request) use($closure) : void{
			$result = $request->getQuery()->getResult()->getRows();
			if(count($result) > 0){
				$data = array_shift($result);
				$id = $data["id"];
				$this->loadGang($id, $closure);
			}else{
				$closure(null);
			}
		});
	}

	public function loadByPlayer(Player $player, ?\Closure $closure = null) : void{
		if($this->inGang($player)){
			if($closure !== null) $closure($this->getPlayerGang($player));
			return;
		}

		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("load_gang_by_player_" . $player->getXuid(),
			new MySqlQuery(
				"main",
				"SELECT gangid FROM gang_members WHERE xuid=?",
				[$player->getXuid()]
			)
		), function(MySqlRequest $request) use($closure) : void{
			$result = $request->getQuery()->getResult()->getRows();
			if(count($result) > 0){
				$data = array_shift($result);
				$id = $data["gangid"];
				$this->loadGang($id, $closure);
			}else{
				if($closure !== null) $closure(null);
			}
		});
	}

	public function onLeave(Player $player) : void{
		if($this->inGang($player)){
			$gang = $this->getPlayerGang($player);
			if(($member = $gang->getMember($player))->hasChanged()){
				$member->sync();
			}
			$this->saveGang($gang->getId());
			if($gang->inBattle()){
				$battle = $gang->getBattle();
				if($battle->isParticipating($player)){
					$battle->removeParticipant($player);
				}
			}
			if(($bm = $this->getBattleManager())->isSpectator($player)){
				$bm->getSpectating($player)->remove();
			}
		}
	}

	public function getPlayerInvites(Player $player) : array{
		$invites = [];
		foreach($this->getGangs() as $gang){
			if(($inv = $gang->getInviteManager()->getInvite($player)) !== null)
				$invites[] = $inv;
		}
		return $invites;
	}

	public function getInviteBy(GangInvite $invite) : ?GangInvite{
		$gang = $this->getGangById($invite->getGang()->getId());
		return $gang->getInviteManager()->getInvite($invite);
	}

	public function hasLeftGang(Player $player) : bool{
		return isset($this->left[strtolower($player->getName())]) && $this->left[strtolower($player->getName())] + (60 * 120) > time();
	}

	public function addLeftGang($player) : void{
		$this->left[strtolower($player instanceof User ? $player->getGamertag() : $player->getName())] = time();
	}

}