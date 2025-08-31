<?php namespace prison\koth;

use core\session\component\{
	ComponentRequest,
	SaveableComponent
};
use core\session\mysqli\data\MySqlQuery;

class KothComponent extends SaveableComponent{

	public ?Game $game = null;

	public int $kills = 0;
	public int $weeklyKills = 0;
	public int $monthlyKills = 0;

	public int $deaths = 0;
	public int $weeklyDeaths = 0;
	public int $monthlyDeaths = 0;

	public int $wins = 0;
	public int $weeklyWins = 0;
	public int $monthlyWins = 0;

	public int $cooldown = 0;
	
	public function getName() : string{
		return "koth";
	}
	
	public function getGame() : ?Game{
		return $this->game;
	}
	
	public function inGame() : bool{
		return $this->game !== null;
	}
	
	public function setGame(?Game $game = null) : void{
		$oldGame = $this->getGame();
		$this->game = $game;
		if($game === null && $oldGame !== null){
			$oldGame->removeScoreboard($this->getPlayer());
		}
	}

	public function getKills() : int{
		return $this->kills;
	}

	public function getWeeklyKills() : int{
		return $this->weeklyKills;
	}

	public function getMonthlyKills() : int{
		return $this->monthlyKills;
	}

	public function addKill() : void{
		$this->kills++;
		//$this->weeklyKills++;
		$this->monthlyKills++;
		$this->setChanged();
	}

	public function getDeaths() : int{
		return $this->deaths;
	}

	public function getWeeklyDeaths() : int{
		return $this->weeklyDeaths;
	}

	public function getMonthlyDeaths() : int{
		return $this->monthlyDeaths;
	}

	public function addDeath() : void{
		$this->deaths++;
		//$this->weeklyDeaths++;
		$this->monthlyDeaths++;
		$this->setChanged();
	}

	public function getWins() : int{
		return $this->wins;
	}

	public function getWeeklyWins() : int{
		return $this->weeklyWins;
	}

	public function getMonthlyWins() : int{
		return $this->monthlyWins;
	}

	public function addWin() : void{
		$this->wins++;
		//$this->weeklyWins++;
		$this->monthlyWins++;
		$this->setChanged();
	}
	
	public function hasCooldown() : bool{
		return $this->getCooldown() >= time();
	}

	public function getCooldown() : int{
		return $this->cooldown;
	}

	public function setCooldown() : void{
		$this->cooldown = time() + (60 * 60 * 2);
		$this->setChanged();
	}

	public function getFormattedCooldown() : string{
		$cooldown = $this->getCooldown() - time();
		$dtF = new \DateTime("@0");
		$dtT = new \DateTime("@$cooldown");
		return $dtF->diff($dtT)->format("%h hours, %i minutes");
	}

	public function delete() : void{
		$this->kills = 0;
		$this->deaths = 0;
		$this->wins = 0;
		$this->weeklyKills = 0;
		$this->weeklyDeaths = 0;
		$this->weeklyWins = 0;
		$this->monthlyKills = 0;
		$this->monthlyDeaths = 0;
		$this->monthlyWins = 0;
		$this->cooldown = 0;
		$this->setChanged();
	}

	public function createTables() : void{
		$db = $this->getSession()->getSessionManager()->getDatabase();
		foreach([
			//"DROP TABLE IF EXISTS koth_stats",
			"CREATE TABLE IF NOT EXISTS koth_stats(
				xuid BIGINT(16) NOT NULL UNIQUE,
				kills INT NOT NULL DEFAULT 0, deaths INT NOT NULL DEFAULT 0, wins INT NOT NULL DEFAULT 0,
				weekly_kills INT NOT NULL DEFAULT 0, weekly_deaths INT NOT NULL DEFAULT 0, weekly_wins INT NOT NULL DEFAULT 0,
				monthly_kills INT NOT NULL DEFAULT 0, monthly_deaths INT NOT NULL DEFAULT 0, monthly_wins INT NOT NULL DEFAULT 0,
				cooldown INT NOT NULL DEFAULT 0
			)",
		] as $query) $db->query($query);
	}

	public function loadAsync() : void{
		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main", "SELECT * FROM koth_stats WHERE xuid=?", [$this->getXuid()]));
		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null) : void{
		$result = $request->getQuery()->getResult();
		$rows = $result->getRows();
		if(count($rows) > 0){
			$data = array_shift($rows);

			$this->kills = $data["kills"];
			$this->weeklyKills = $data["weekly_kills"];
			$this->monthlyKills = $data["monthly_kills"];

			$this->deaths = $data["deaths"];
			$this->weeklyDeaths = $data["weekly_deaths"];
			$this->monthlyDeaths = $data["monthly_deaths"];

			$this->wins = $data["wins"];
			$this->weeklyWins = $data["weekly_wins"];
			$this->monthlyWins = $data["monthly_wins"];

			$this->cooldown = $data["cooldown"];
		}

		parent::finishLoadAsync($request);
	}

	public function verifyChange() : bool{
		$verify = $this->getChangeVerify();
		return $this->getKills() !== $verify["kills"] ||
			$this->getDeaths() !== $verify["deaths"] ||
			$this->getWins() !== $verify["wins"] ||
			$this->getCooldown() !== $verify["cooldown"];
	}

	public function saveAsync() : void{
		if(!$this->hasChanged() || !$this->isLoaded()) return;

		$this->setChangeVerify([
			"kills" => $this->getKills(),
			"deaths" => $this->getDeaths(),
			"wins" => $this->getWins(),
			"cooldown" => $this->getCooldown(),
		]);

		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main",
			"INSERT INTO koth_stats(
				xuid,
				kills, weekly_kills, monthly_kills,
				deaths, weekly_deaths, monthly_deaths,
				wins, weekly_wins, monthly_wins,
				cooldown
			) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
				kills=VALUES(kills), weekly_kills=VALUES(weekly_kills), monthly_kills=VALUES(monthly_kills),
				deaths=VALUES(deaths), weekly_deaths=VALUES(weekly_deaths), monthly_deaths=VALUES(monthly_deaths),
				wins=VALUES(wins), weekly_wins=VALUES(weekly_wins), monthly_wins=VALUES(monthly_wins),
				cooldown=VALUES(cooldown)",
			[
				$this->getXuid(),
				$this->getKills(), $this->getWeeklyKills(), $this->getMonthlyKills(),
				$this->getDeaths(), $this->getWeeklyDeaths(), $this->getMonthlyDeaths(),
				$this->getWins(), $this->getWeeklyWins(), $this->getMonthlyWins(),
				$this->getCooldown()
			]
		));
		$this->newRequest($request, ComponentRequest::TYPE_SAVE);
		parent::saveAsync();
	}

	public function save() : bool{
		if(!$this->hasChanged() || !$this->isLoaded()) return false;

		$xuid = $this->getXuid();

		$kills = $this->getKills();
		$weeklyKills = $this->getWeeklyKills();
		$monthlyKills = $this->getMonthlyKills();

		$deaths = $this->getDeaths();
		$weeklyDeaths = $this->getWeeklyDeaths();
		$monthlyDeaths = $this->getMonthlyDeaths();

		$wins = $this->getWins();
		$weeklyWins = $this->getWeeklyWins();
		$monthlyWins = $this->getMonthlyWins();

		$cooldown = $this->getCooldown();

		$db = $this->getSession()->getSessionManager()->getDatabase();

		$stmt = $db->prepare("INSERT INTO koth_stats(
				xuid,
				kills, weekly_kills, monthly_kills,
				deaths, weekly_deaths, monthly_deaths,
				wins, weekly_wins, monthly_wins,
				cooldown
			) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
				kills=VALUES(kills), weekly_kills=VALUES(weekly_kills), monthly_kills=VALUES(monthly_kills),
				deaths=VALUES(deaths), weekly_deaths=VALUES(weekly_deaths), monthly_deaths=VALUES(monthly_deaths),
				wins=VALUES(wins), weekly_wins=VALUES(weekly_wins), monthly_wins=VALUES(monthly_wins),
				cooldown=VALUES(cooldown)"
		);
		$stmt->bind_param("iiiiiiiiiii", $xuid, $kills, $weeklyKills, $monthlyKills, $deaths, $weeklyDeaths, $monthlyDeaths, $wins, $weeklyWins, $monthlyWins, $cooldown);
		$stmt->execute();
		$stmt->close();
		
		return parent::save();
	}

	public function getSerializedData(): array {
		return [
			"kills" => $this->getKills(),
			"weekly_kills" => $this->getWeeklyKills(),
			"monthly_kills" => $this->getMonthlyKills(),
			"deaths" => $this->getDeaths(),
			"weekly_deaths" => $this->getWeeklyDeaths(),
			"monthly_deaths" => $this->getMonthlyDeaths(),
			"wins" => $this->getWins(),
			"weekly_wins" => $this->getWeeklyWins(),
			"monthly_wins" => $this->getMonthlyWins(),
			"cooldown" => $this->getCooldown()
		];
	}

	public function applySerializedData(array $data): void {
		$this->kills = $data["kills"];
		$this->weeklyKills = $data["weekly_kills"];
		$this->monthlyKills = $data["monthly_kills"];

		$this->deaths = $data["deaths"];
		$this->weeklyDeaths = $data["weekly_deaths"];
		$this->monthlyDeaths = $data["monthly_deaths"];

		$this->wins = $data["wins"];
		$this->weeklyWins = $data["weekly_wins"];
		$this->monthlyWins = $data["monthly_wins"];

		$this->cooldown = $data["cooldown"];
	}

}