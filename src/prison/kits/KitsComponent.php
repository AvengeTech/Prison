<?php namespace prison\kits;

use core\session\component\{
	ComponentRequest,
	SaveableComponent
};
use core\session\mysqli\data\MySqlQuery;

class KitsComponent extends SaveableComponent{

	public array $cooldowns = [];

	public function getName() : string{
		return "kits";
	}

	public function getCooldown(string $kitname) : int{
		return $this->cooldowns[$kitname] ?? 0;
	}

	public function getFormattedCooldown(string $kitname) : string{
		$seconds = $this->getCooldown($kitname) - time();
		$dtF = new \DateTime("@0");
		$dtT = new \DateTime("@$seconds");
		return $dtF->diff($dtT)->format("%a days, %h hours, %i minutes");
	}

	public function hasCooldown(string $kitname) : bool{
		return $this->getCooldown($kitname) - time() > 0;
	}

	public function setCooldown(string $kitname, int $cooldown) : void{
		$this->cooldowns[$kitname] = $cooldown;
		$this->setChanged();
	}

	public function createTables() : void{
		$db = $this->getSession()->getSessionManager()->getDatabase();
		foreach([
			"CREATE TABLE IF NOT EXISTS kit_cooldown(xuid BIGINT(16) NOT NULL UNIQUE,
				starter BIGINT(16) NOT NULL DEFAULT 0,
				daily BIGINT(16) NOT NULL DEFAULT 0,
				weekly BIGINT(16) NOT NULL DEFAULT 0,
				monthly BIGINT(16) NOT NULL DEFAULT 0,

				endermite BIGINT(16) NOT NULL DEFAULT 0,
				blaze BIGINT(16) NOT NULL DEFAULT 0,
				ghast BIGINT(16) NOT NULL DEFAULT 0,
				enderman BIGINT(16) NOT NULL DEFAULT 0,
				wither BIGINT(16) NOT NULL DEFAULT 0,
				enderdragon BIGINT(16) NOT NULL DEFAULT 0
			)",
		] as $query) $db->query($query);
	}

	public function loadAsync() : void{
		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main", "SELECT * FROM kit_cooldown WHERE xuid=?", [$this->getXuid()]));
		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null) : void{
		$result = $request->getQuery()->getResult();
		$rows = (array) $result->getRows();
		if(count($rows) > 0){
			$data = array_shift($rows);
			$this->cooldowns = [
				"starter" => $data["starter"],
				"daily" => $data["daily"],
				"weekly" => $data["weekly"],
				"monthly" => $data["monthly"],

				"endermite" => $data["endermite"],
				"blaze" => $data["blaze"],
				"ghast" => $data["ghast"],
				"enderman" => $data["enderman"],
				"wither" => $data["wither"],
				"enderdragon" => $data["enderdragon"]
			];
		}

		parent::finishLoadAsync($request);
	}

	public function verifyChange() : bool{
		$verify = $this->getChangeVerify();
		return $this->cooldowns !== $verify["cooldowns"];
	}

	public function saveAsync() : void{
		if(!$this->hasChanged() || !$this->isLoaded()) return;

		$this->setChangeVerify([
			"cooldowns" => $this->cooldowns,
		]);

		$player = $this->getPlayer();
		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main",
			"INSERT INTO kit_cooldown(xuid, starter, daily, weekly, monthly, endermite, blaze, ghast, enderman, wither, enderdragon)
			VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE 
			starter=VALUES(starter), daily=VALUES(daily), weekly=VALUES(weekly), monthly=VALUES(monthly), 
			endermite=VALUES(endermite), blaze=VALUES(blaze), ghast=VALUES(ghast),
			enderman=VALUES(enderman), wither=VALUES(wither), enderdragon=VALUES(enderdragon)",
			[
				$this->getXuid(),
				$this->getCooldown("starter"),
				$this->getCooldown("daily"),
				$this->getCooldown("weekly"),
				$this->getCooldown("monthly"),

				$this->getCooldown("endermite"), $this->getCooldown("blaze"),
				$this->getCooldown("ghast"), $this->getCooldown("enderman"),
				$this->getCooldown("wither"), $this->getCooldown("enderdragon")
			]
		));
		$this->newRequest($request, ComponentRequest::TYPE_SAVE);
		parent::saveAsync();
	}

	public function save() : bool{
		if(!$this->hasChanged() || !$this->isLoaded()) return false;

		$xuid = $this->getXuid();

		$db = $this->getSession()->getSessionManager()->getDatabase();

		$starter = $this->getCooldown("starter");
		$daily = $this->getCooldown("daily");
		$weekly = $this->getCooldown("weekly");
		$monthly = $this->getCooldown("monthly");
		$endermite = $this->getCooldown("endermite");
		$blaze = $this->getCooldown("blaze");
		$ghast = $this->getCooldown("ghast");
		$enderman = $this->getCooldown("enderman");
		$wither = $this->getCooldown("wither");
		$enderdragon = $this->getCooldown("enderdragon");

		$stmt = $db->prepare("INSERT INTO kit_cooldown(xuid, 
			starter, daily, weekly, monthly, endermite, blaze, ghast, enderman, wither, enderdragon) VALUES(
			?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE 
			starter=VALUES(starter), daily=VALUES(daily), weekly=VALUES(weekly), monthly=VALUES(monthly), 
			endermite=VALUES(endermite), blaze=VALUES(blaze), ghast=VALUES(ghast),
			enderman=VALUES(enderman), wither=VALUES(wither), enderdragon=VALUES(enderdragon)
		");
		$stmt->bind_param("iiiiiiiiiii", $xuid, $starter, $daily, $weekly, $monthly, $endermite, $blaze, $ghast, $enderman, $wither, $enderdragon);
		$stmt->execute();
		$stmt->close();

		return parent::save();
	}


	public function getSerializedData(): array {
		$starter = $this->getCooldown("starter");
		$daily = $this->getCooldown("daily");
		$weekly = $this->getCooldown("weekly");
		$monthly = $this->getCooldown("monthly");
		$endermite = $this->getCooldown("endermite");
		$blaze = $this->getCooldown("blaze");
		$ghast = $this->getCooldown("ghast");
		$enderman = $this->getCooldown("enderman");
		$wither = $this->getCooldown("wither");
		$enderdragon = $this->getCooldown("enderdragon");

		return [
			"starter" => $starter,
			"daily" => $daily,
			"weekly" => $weekly,
			"monthly" => $monthly,

			"endermite" => $endermite,
			"blaze" => $blaze,
			"ghast" => $ghast,
			"enderman" => $enderman,
			"wither" => $wither,
			"enderdragon" => $enderdragon
		];
	}

	public function applySerializedData(array $data): void {
		$this->cooldowns = [
			"starter" => $data["starter"],
			"daily" => $data["daily"],
			"weekly" => $data["weekly"],
			"monthly" => $data["monthly"],

			"endermite" => $data["endermite"],
			"blaze" => $data["blaze"],
			"ghast" => $data["ghast"],
			"enderman" => $data["enderman"],
			"wither" => $data["wither"],
			"enderdragon" => $data["enderdragon"]
		];
	}
}