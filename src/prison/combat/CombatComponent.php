<?php namespace prison\combat;

use pocketmine\Server;
use pocketmine\player\{
	GameMode,
	Player
};

use prison\Prison;
use prison\PrisonPlayer;

use core\session\component\{
	ComponentRequest,
	SaveableComponent
};
use core\session\mysqli\data\MySqlQuery;
use core\utils\TextFormat;

class CombatComponent extends SaveableComponent{

	public int $ticks = 0;

	//bounty
	public int $bountyValue = 0;

	//stats
	public int $pvp_kills = 0;
	public int $pvp_deaths = 0;
	public int $mine_kills = 0;
	public int $mine_monthly_kills = 0;
	public int $mine_deaths = 0;
	public int $mine_monthly_deaths = 0;
	public int $grinder_kills = 0;

	//mode
	public bool $pvpMode = false;

	public int $invincible = 0;

	public bool $tagClosed = false;
	public ?array $tagged = null;

	public function getName() : string{
		return "combat";
	}

	public function tick() : void{
		$this->ticks++;
		if($this->ticks %4 !== 0) return;

		$player = $this->getPlayer();
		
		if($this->isTagged() && $this->canUntag()){
			$this->untag();
			$player?->sendMessage(TextFormat::YN . "You are no longer in combat.");
		}
		if($this->isInvincible()){
			if($this->invincible - time() <= 0){
				$this->invincible = 0;
				$player?->sendMessage(TextFormat::RI . "You are no longer invincible.");
			}
		}
	}

	public function hasBounty() : bool{
		return $this->getBountyValue() > 0;
	}

	public function getBountyValue() : int{
		return $this->bountyValue;
	}

	public function setBountyValue(int $value) : void{
		$this->bountyValue = $value;
		$this->setChanged();
		$this->getPlayer()?->updateNametag();
	}

	public function addBountyValue(int $value) : void{
		$this->setBountyValue($this->getBountyValue() + $value);
	}

	public function takeBountyValue(int $value) : void{
		$this->addBountyValue(-$value);
	}


	public function getPvPKills() : int{
		return $this->pvp_kills;
	}

	public function setPvPKills(int $kills) : void{
		$this->pvp_kills = $kills;
		$this->setChanged();
	}

	public function addPvPKill() : void{
		$this->setPvPKills($this->getPvPKills() + 1);
	}

	public function getPvPDeaths() : int{
		return $this->pvp_deaths;
	}

	public function setPvPDeaths(int $deaths) : void{
		$this->pvp_deaths = $deaths;
		$this->setChanged();
	}

	public function addPvPDeath() : void{
		$this->setPvPDeaths($this->getPvPDeaths() + 1);
	}

	public function getPvPRatio() : int{
		if($this->getPvPDeaths() === 0) return $this->getPvPKills();
		return round($this->getPvPKills() / $this->getPvPDeaths(), 2);
	}

	public function getMineKills() : int{
		return $this->mine_kills;
	}

	public function setMineKills(int $kills) : void{
		$this->mine_kills = $kills;
		$this->setChanged();
	}

	public function getMineMonthlyKills() : int{
		return $this->mine_monthly_kills;
	}

	public function setMineMonthlyKills(int $kills) : void{
		$this->mine_monthly_kills = $kills;
		$this->setChanged();
	}

	public function addMineKill() : void{
		$this->setMineKills($this->getMineKills() + 1);
		$this->setMineMonthlyKills($this->getMineMonthlyKills() + 1);
	}

	public function getMineDeaths() : int{
		return $this->mine_deaths;
	}

	public function setMineDeaths(int $deaths) : void{
		$this->mine_deaths = $deaths;
		$this->setChanged();
	}

	public function getMineMonthlyDeaths() : int{
		return $this->mine_monthly_deaths;
	}

	public function setMineMonthlyDeaths(int $deaths) : void{
		$this->mine_monthly_deaths = $deaths;
		$this->setChanged();
	}

	public function addMineDeath() : void{
		$this->setMineDeaths($this->getMineDeaths() + 1);
		$this->setMineMonthlyDeaths($this->getMineMonthlyDeaths() + 1);
	}

	public function getMineRatio() : int{
		if($this->getMineDeaths() === 0) return $this->getMineKills();
		return round($this->getMineKills() / $this->getMineDeaths(), 2);
	}

	public function getGrinderKills() : int{
		if(!$this->isLoaded()) $this->loadAsync();
		return $this->grinder_kills;
	}

	public function setGrinderKills(int $kills) : void{
		$this->grinder_kills = $kills;
		$this->setChanged();
	}

	public function addGrinderKill() : void{
		$this->setGrinderKills($this->getGrinderKills() + 1);
	}

	public function inPvPMode() : bool{
		return $this->pvpMode;
	}

	public function togglePvPMode() : void{
		$this->pvpMode = !$this->pvpMode;
		$player = $this->getPlayer();
		/** @var PrisonPlayer $player */
		if(!$player instanceof Player) return;
		if(!$this->inPvPMode()){
			if(!$player->getGameSession()->getMines()->inMine() && !$player->getGameSession()->getKoth()->inGame()){
				$player->setAllowFlight(true);
			}
		}else{
			$player->setAllowFlight(false);
			$player->setGamemode(GameMode::CREATIVE());
			$player->setGamemode($player->inSpawn() ? GameMode::ADVENTURE() : GameMode::SURVIVAL());

			if($player->inFlightMode()) $player->setFlightMode(false);
		}

		$player->updateNametag();
		$player->updateOwnNametags();
		$player->updateChatFormat();
	}

	public function isInvincible() : bool{
		return $this->invincible - time() >= 0;
	}

	public function setInvincible(int $seconds = 5) : void{
		$this->invincible = time() + $seconds;
		$this->getPlayer()?->sendMessage(TextFormat::YN . "You have invincibility for " . TextFormat::YELLOW . $seconds . TextFormat::GRAY . " seconds.");
	}

	public function disableTag() : void{
		$this->tagClosed = true;
	}

	public function tag(PrisonPlayer $hitter): void {
		if($this->tagClosed) return;
		if (!$this->isTagged()) {
			$this->getSession()->getPlayer()?->updateOwnNametags(true);
		}
		$this->tagged = [
			time(),
			$hitter->getName()
		];
	}

	public function isTagged() : bool{
		if($this->tagClosed) return false;
		return $this->tagged !== null;
	}

	public function getTagTime() : int{
		return $this->tagged[0] ?? 0;
	}

	public function getTagger() : ?Player{
		if (!isset($this->tagged[1])) return null;
		return Server::getInstance()->getPlayerExact($this->tagged[1]);
	}

	public function canUntag() : bool{
		return ($this->getTagTime() + 10) - time() <= 0;
	}

	public function untag() : void{
		$this->tagged = null;
		$this->getSession()->getPlayer()?->updateOwnNametags();
	}

	public function punish() : void{
		$player = $this->getPlayer();
		$tagger = $this->getTagger();
		if($player instanceof Player && $tagger instanceof Player){
			Prison::getInstance()->getCombat()->processKill($tagger, $player);
		}
		$this->untag();
	}

	public function createTables() : void{
		$db = $this->getSession()->getSessionManager()->getDatabase();
		foreach([
			"CREATE TABLE IF NOT EXISTS bounty_data(xuid BIGINT(16) NOT NULL UNIQUE, value INT NOT NULL DEFAULT 0)",
			//"DROP TABLE IF EXISTS combat_stats",
			"CREATE TABLE IF NOT EXISTS combat_stats(
				xuid BIGINT(16) NOT NULL UNIQUE,
				pvp_kills INT NOT NULL DEFAULT 0,
				pvp_deaths INT NOT NULL DEFAULT 0,
				mine_kills INT NOT NULL DEFAULT 0,
				mine_monthly_kills INT NOT NULL DEFAULT 0,
				mine_deaths INT NOT NULL DEFAULT 0,
				mine_monthly_deaths INT NOT NULL DEFAULT 0,
				grinder_kills INT NOT NULL DEFAULT 0
			)",
		] as $query) $db->query($query);
	}

	public function loadAsync() : void{
		$request = new ComponentRequest($this->getXuid(), $this->getName(), [
			new MySqlQuery("bounty", "SELECT * FROM bounty_data WHERE xuid=?", [$this->getXuid()]),
			new MySqlQuery("stats", "SELECT * FROM combat_stats WHERE xuid=?", [$this->getXuid()])
		]);
		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null) : void{
		$result = $request->getQuery("bounty")->getResult();
		$rows = (array) $result->getRows();
		if(count($rows) > 0){
			$data = array_shift($rows);
			$this->bountyValue = $data["value"];
		}

		$result = $request->getQuery("stats")->getResult();
		$rows = (array) $result->getRows();
		if(count($rows) > 0){
			$data = array_shift($rows);
			$this->pvp_kills = $data["pvp_kills"];
			$this->pvp_deaths = $data["pvp_deaths"];
			$this->mine_kills = $data["mine_kills"];
			$this->mine_monthly_kills = $data["mine_monthly_kills"];
			$this->mine_deaths = $data["mine_deaths"];
			$this->mine_monthly_deaths = $data["mine_monthly_deaths"];
			$this->grinder_kills = $data["grinder_kills"];
		}

		parent::finishLoadAsync($request);
	}

	public function verifyChange() : bool{
		$player = $this->getPlayer();
		$verify = $this->getChangeVerify();
		return
			$this->getBountyValue() !== $verify["bounty"] ||
			$this->getPvPKills() !== $verify["pvp_kills"] ||
			$this->getPvPDeaths() !== $verify["pvp_deaths"] ||
			$this->getMineKills() !== $verify["mine_kills"] ||
			$this->getMineDeaths() !== $verify["mine_deaths"] ||
			$this->getGrinderKills() !== $verify["grinder_kills"];
	}

	public function saveAsync() : void{
		if(!$this->hasChanged() || !$this->isLoaded()) return;

		$this->setChangeVerify([
			"bounty" => $this->getBountyValue(),

			"pvp_kills" => $this->getPvPKills(),
			"pvp_deaths" => $this->getPvPDeaths(),
			"mine_kills" => $this->getMineKills(),
			"mine_deaths" => $this->getMineDeaths(),
			"grinder_kills" => $this->getGrinderKills(),
		]);

		$player = $this->getPlayer();
		$request = new ComponentRequest($this->getXuid(), $this->getName(), [
			new MySqlQuery("bounty", "INSERT INTO bounty_data(xuid, value) VALUES(?, ?) ON DUPLICATE KEY UPDATE value=VALUES(value)", [$this->getXuid(), $this->getBountyValue()]),
			new MySqlQuery("stats",
				"INSERT INTO combat_stats(
					xuid,
					pvp_kills,
					pvp_deaths,
					mine_kills,
					mine_monthly_kills,
					mine_deaths,
					mine_monthly_deaths,
					grinder_kills
				) VALUES(?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
					pvp_kills=VALUES(pvp_kills),
					pvp_deaths=VALUES(pvp_deaths),
					mine_kills=VALUES(mine_kills),
					mine_monthly_kills=VALUES(mine_monthly_kills),
					mine_deaths=VALUES(mine_deaths),
					mine_monthly_deaths=VALUES(mine_monthly_deaths),
					grinder_kills=VALUES(grinder_kills);",
				[
					$this->getXuid(),
					$this->getPvPKills(), $this->getPvPDeaths(),
					$this->getMineKills(), $this->getMineMonthlyKills(),
					$this->getMineDeaths(), $this->getMineMonthlyDeaths(),
					$this->getGrinderKills()
				]
			),
		]);
		$this->newRequest($request, ComponentRequest::TYPE_SAVE);
		parent::saveAsync();
	}

	public function save() : bool{
		if(!$this->hasChanged() || !$this->isLoaded()) return false;

		$db = $this->getSession()->getSessionManager()->getDatabase();
		$xuid = $this->getXuid();

		$value = $this->getBountyValue();
		$stmt = $db->prepare("INSERT INTO bounty_data(xuid, value) VALUES(?, ?) ON DUPLICATE KEY UPDATE value=VALUES(value)");
		$stmt->bind_param("ii", $xuid, $value);
		$stmt->execute();
		$stmt->close();

		$pvp_kills = $this->getPvPKills();
		$pvp_deaths = $this->getPvPDeaths();
		$mine_kills = $this->getMineKills();
		$mine_monthly_kills = $this->getMineMonthlyKills();
		$mine_deaths = $this->getMineDeaths();
		$mine_monthly_deaths = $this->getMineMonthlyDeaths();
		$grinder_kills = $this->getGrinderKills();
		$stmt = $db->prepare(
			"INSERT INTO combat_stats(
				xuid,
				pvp_kills,
				pvp_deaths,
				mine_kills,
				mine_monthly_kills,
				mine_deaths,
				mine_monthly_deaths,
				grinder_kills
			) VALUES(?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
			pvp_kills=VALUES(pvp_kills),
			pvp_deaths=VALUES(pvp_deaths),
			mine_kills=VALUES(mine_kills),
			mine_monthly_kills=VALUES(mine_monthly_kills),
			mine_deaths=VALUES(mine_deaths),
			mine_monthly_deaths=VALUES(mine_monthly_deaths),
			grinder_kills=VALUES(grinder_kills);"
		);
		$stmt->bind_param("iiiiiiii", $xuid, $pvp_kills, $pvp_deaths, $mine_kills, $mine_monthly_kills, $mine_deaths, $mine_monthly_deaths, $grinder_kills);
		$stmt->execute();
		$stmt->close();

		return parent::save();
	}

	public function getSerializedData(): array {
		return [
			"bounty" => $this->getBountyValue(),

			"pvp_kills" => $this->getPvPKills(),
			"pvp_deaths" => $this->getPvPDeaths(),
			"mine_kills" => $this->getMineKills(),
			"mine_monthly_kills" => $this->getMineMonthlyKills(),
			"mine_deaths" => $this->getMineDeaths(),
			"mine_monthly_deaths" => $this->getMineMonthlyDeaths(),
			"grinder_kills" => $this->getGrinderKills(),
		];
	}

	public function applySerializedData(array $data): void {
		$this->pvp_kills = $data["pvp_kills"];
		$this->pvp_deaths = $data["pvp_deaths"];
		$this->mine_kills = $data["mine_kills"];
		$this->mine_monthly_kills = $data["mine_monthly_kills"];
		$this->mine_deaths = $data["mine_deaths"];
		$this->mine_monthly_deaths = $data["mine_monthly_deaths"];
		$this->grinder_kills = $data["grinder_kills"];
	}

}