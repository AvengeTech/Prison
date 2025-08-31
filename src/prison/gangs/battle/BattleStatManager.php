<?php namespace prison\gangs\battle;

use prison\Prison;
use prison\gangs\objects\Gang;

use core\session\mysqli\data\{
	MySqlQuery,
	MySqlRequest
};

class BattleStatManager{
	
	public int $kills = 0;
	public int $deaths = 0;

	public int $wins = 0;
	public int $losses = 0;
	public int $draws = 0;

	public array $recentStats = [];

	public bool $loaded = false;
	public bool $changed = false;

	public function __construct(public Gang $gang){}
	
	public function isLoaded() : bool{
		return $this->loaded;
	}
	
	public function setLoaded(bool $loaded = true) : void{
		$this->loaded = $loaded;
	}

	public function load() : void{
		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("delete_gang_battlestats_" . $this->getGang()->getId(),
			new MySqlQuery(
				"main",
				"SELECT * FROM gang_battle_data WHERE id=?",
				[$this->getGang()->getId()]
			)
		), function(MySqlRequest $request) : void{
			$rows = $request->getQuery()->getResult()->getRows();
			if(count($rows) > 0){
				$data = array_shift($rows);
				$this->kills = $data["kills"];
				$this->deaths = $data["deaths"];
				$this->wins = $data["wins"];
				$this->losses = $data["losses"];
				$this->draws = $data["draws"];
			}
			$this->setLoaded();
			$this->getGang()->tryLoadedClosure();
		});
	}

	public function getGang() : Gang{
		return $this->gang;
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

	public function getWins() : int{
		return $this->wins;
	}

	public function addWin() : void{
		$this->wins++;
		$this->setChanged();
	}

	public function getLosses() : int{
		return $this->losses;
	}

	public function addLoss() : void{
		$this->losses++;
		$this->setChanged();
	}

	public function getDraws() : int{
		return $this->draws;
	}

	public function addDraw() : void{
		$this->draws++;
		$this->setChanged();
	}

	public function getRecentBattleStats() : array{
		return $this->recentStats;
	}

	public function addRecentBattleStats(BattleStats $stats) : void{
		$this->recentStats[] = $stats;
	}

	public function isChanged() : bool{
		return $this->changed;
	}

	public function setChanged(bool $changed = true) : void{
		$this->changed = $changed;
	}

	public function delete() : void{
		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("delete_gang_battlestats_" . $this->getGang()->getId(),
			new MySqlQuery(
				"main",
				"DELETE FROM gang_battle_data WHERE id=?",
				[$this->getGang()->getId()]
			)
		), function(MySqlRequest $request) : void{});
	}

	public function save(bool $async = true) : void{
		if(!$this->isChanged()) return;
		if($async){
			Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("save_gang_battlestats_" . $this->getGang()->getId(),
				new MySqlQuery(
					"main",
					"INSERT INTO gang_battle_data(id, kills, deaths, wins, losses, draws) VALUES(?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE kills=VALUES(kills), deaths=VALUES(deaths), wins=VALUES(wins), losses=VALUES(losses), draws=VALUES(draws)",
					[
						$this->getGang()->getId(),
						$this->getKills(),
						$this->getDeaths(),
						$this->getWins(),
						$this->getLosses(),
						$this->getDraws()
					]
				)
			), function(MySqlRequest $request) : void{
				$this->setChanged(false);
			});
		}else{
			$id = $this->getGang()->getId();

			$kills = $this->getKills();
			$deaths = $this->getDeaths();
			$wins = $this->getWins();
			$losses = $this->getLosses();
			$draws = $this->getDraws();

			$db = Prison::getInstance()->getSessionManager()->getDatabase();
			$stmt = $db->prepare("INSERT INTO gang_battle_data(id, kills, deaths, wins, losses, draws) VALUES(?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE kills=VALUES(kills), deaths=VALUES(deaths), wins=VALUES(wins), losses=VALUES(losses), draws=VALUES(draws)");
			$stmt->bind_param("iiiiii", $id, $kills, $deaths, $wins, $losses, $draws);
			$stmt->execute();
			$stmt->close();

			$this->setChanged(false);	
		}
	}

}