<?php namespace prison\gangs\battle;

use pocketmine\{
	player\Player,
	Server
};

use prison\Prison;
use prison\enchantments\ItemData;
use prison\gangs\objects\Gang;
use prison\gangs\GangManager;
use prison\gangs\battle\arena\{
	Arena,
	ArenaData,
	Half,
	Center
};

use core\Core;
use core\utils\conversion\LegacyItemIds;
use core\utils\ItemRegistry;
use core\utils\TextFormat;
use pocketmine\item\StringToItemParser;

class BattleManager{

	const BATTLE_TROPHY_COOLDOWN = 3600;

	public $gangManager;

	public $arenas = [];
	public $kits = [];

	public static $battleId = 0;
	public $battles = [];

	public $recent = [];

	public function __construct(GangManager $gangManager){
		$this->gangManager = $gangManager;
		$this->loadArenas();
		$this->setupKits();
	}

	public function tick() : void{
		foreach($this->getBattles() as $battle){
			$battle->tick();
		}
	}

	public function getGangManager() : GangManager{
		return $this->gangManager;
	}

	public function getArenas() : array{
		return $this->arenas;
	}

	public function getArena(int $id) : ?Arena{
		$arena = $this->arenas[$id] ?? null;
		if($arena instanceof Arena)
			return clone $arena;

		return null;
	}

	public function isArenaOccupied(int $id) : bool{
		foreach($this->getBattles() as $battle){
			if($battle->getArena()->getId() == $id) return true;
		}
		return false;
	}

	public function getFreeArena() : ?Arena{
		foreach($this->getArenas() as $arena){
			if(!$this->isArenaOccupied($arena->getId())) return $arena;
		}
		return null;
	}

	public function loadArenas() : void{
		$data = ArenaData::genArenaData();
		foreach($data as $key => $entry){
			Server::getInstance()->getWorldManager()->loadWorld($entry["level"], true);
			$level = Server::getInstance()->getWorldManager()->getWorldByName($entry["level"]);

			$arena = new Arena($key, $entry["corner1"]->add(-0.5,0,0.5), $entry["corner2"]->add(-0.5,0,0.5), $level);
			$center = new Center($entry["center"][0]->add(-0.5,0,0.5), $entry["center"][1]->add(-0.5,0,0.5));
			$halves = [];
			foreach($entry["halves"] as $k => $half){
				$halves[$k] = new Half($k, $half[0]->add(-0.5,0,0.5), $half[1]->add(-0.5,0,0.5));
			}

			$arena->setCenter($center);
			$arena->setHalves($halves);

			$this->arenas[$key] = $arena;
		}
	}

	public function getKits() : array{
		return $this->kits;
	}

	public function getKit(string $id) : ?BattleKit{
		return $this->kits[$id] ?? null;
	}

	public function setupKits() : void{
		foreach(BattleKitData::KITS as $id => $kitdata){
			$items = [];
			foreach(($kitdata["items"] ?? []) as $idata){
				// $item = ItemRegistry::getItemById(LegacyItemIds::legacyIdToTypeId($idata["id"], $idata["dmg"] ?? 0), -1, $idata["count"] ?? 1);
				$item = StringToItemParser::getInstance()->parse($idata['id'])->setCount(($idata['count'] ?? 1));
				if(isset($idata["name"])) $item->setCustomName($idata["name"]);
				$ida = new ItemData($item);
				$enchantments = $idata["enchantments"] ?? [];
				foreach($enchantments as $name => $level){
					$ench = Prison::getInstance()->getEnchantments()->getEnchantmentByName($name);
					if($ench !== null){
						$ida->addEnchantment($ench, $level);
					}
				}
				$items[] = $ida->getItem();
			}
			$armor = [];
			foreach(($kitdata["armor"] ?? []) as $slot => $idata){
				// $item = ItemRegistry::getItemById(LegacyItemIds::legacyIdToTypeId($idata["id"], $idata["dmg"] ?? 0), -1, $idata["count"] ?? 1);
				$item = StringToItemParser::getInstance()->parse($idata['id'])->setCount(($idata['count'] ?? 1));
				if(isset($idata["name"])) $item->setCustomName($idata["name"]);
				$ida = new ItemData($item);
				$enchantments = $idata["enchantments"] ?? [];
				foreach($enchantments as $name => $level){
					$ench = Prison::getInstance()->getEnchantments()->getEnchantmentByName($name);
					if($ench !== null){
						$ida->addEnchantment($ench, $level);
					}
				}
				$armor[$slot] = $ida->getItem();
			}
			$kit = new BattleKit($id, $kitdata["name"], $items, $armor);
			$this->kits[$id] = $kit;
		}
	}

	public function cancelAllBattles(string $reason = "Unknown") : void{
		foreach($this->getBattles() as $battle){
			$battle->cancel($reason);
		}
	}

	/**
	 * Should prevent subserver clashing teehee
	 */
	public function getNewBattleId() : int{
		$num = self::$battleId++;
		if(!isset($this->battles[$num])){
			return $num;
		}else return $this->getNewBattleId();
	}

	public function getBattles(bool $onlyStarted = false) : array{
		if(!$onlyStarted) return $this->battles;
		$battles = [];
		foreach($this->getBattles() as $id => $battle){
			if($battle->getStatus() >= Battle::GAME_GET_READY){
				$battles[$id] = $battle;
			}
		}
		return $battles;
	}

	public function getBattle(int $id) : ?Battle{
		return $this->battles[$id] ?? null;
	}

	public function getBattleByBattle(Battle $battle) : ?Battle{
		return $this->getBattle($battle->getId());
	}

	public function addBattle(Battle $battle) : void{
		$this->battles[$battle->getId()] = $battle;
	}

	public function inBattle(Gang $gang) : bool{
		foreach($this->getBattles() as $battle){
			if(
				$battle->getGang1()->getId() == ($id = $gang->getId()) ||
				$battle->getGang2()->getId() == $id
			) return true;
		}
		return false;
	}

	public function getBattleByGang(Gang $gang) : ?Battle{
		foreach($this->getBattles() as $battle){
			if(
				$battle->getGang1()->getId() == ($id = $gang->getId()) ||
				$battle->getGang2()->getId() == $id
			) return $battle;
		}
		return null;
	}

	public function cancelBattle(int $id, string $reason = "") : bool{
		foreach($this->getBattles() as $battle){
			if($battle->getId() == $id){
				if($reason !== ""){
					foreach($battle->getParticipants() as $participant){
						$pl = $participant->getPlayer();
						if($pl instanceof Player){
							$pl->sendMessage(TextFormat::RI . "The battle you were in was cancelled! Reason: " . $reason);
						}
					}
				}
				unset($this->battles[$id]);
				Core::getInstance()->getEntities()->getFloatingText()->getText("battle-spectate")->update();
				return true;
			}
		}
		return false;
	}

	public function hasBattledRecently(Gang $gang, Gang $battled) : bool{
		if(isset($this->recent[$gang->getId()])){
			$recents = $this->recent[$gang->getId()];
			foreach($recents as $id => $time){
				if($id == $battled->getId() && $time > time())
					return true;
			}
		}
		return false;
	}

	public function addBattledRecently(Gang $gang, Gang $battled) : void{
		if(!isset($this->recent[$gang->getId()])) $this->recent[$gang->getId()] = [];
		if(!isset($this->recent[$battled->getId()])) $this->recent[$battled->getId()] = [];

		$this->recent[$gang->getId()][$battled->getId()] = time() + self::BATTLE_TROPHY_COOLDOWN;
		$this->recent[$battled->getId()][$gang->getId()] = time() + self::BATTLE_TROPHY_COOLDOWN;
	}

	public function isSpectator(Player $player) : bool{
		return $this->getSpectating($player) !== null;
	}

	public function getSpectating(Player $player) : ?Spectator{
		foreach($this->getBattles() as $battle){
			if($battle->isSpectator($player)) return $battle->getSpectator($player);
		}
		return null;
	}

}