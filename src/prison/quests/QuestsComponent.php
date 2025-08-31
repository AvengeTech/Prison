<?php namespace prison\quests;

use core\session\component\{
	ComponentRequest,
	SaveableComponent
};
use core\session\mysqli\data\MySqlQuery;

class QuestsComponent extends SaveableComponent{

	const QUEST_COOLDOWN = 43200;
	const QUEST_COOLDOWN_ADDITION = 14400;

	public ?Quest $currentQuest = null;

	public int $completed = 0;
	public int $points = 0;

	public int $cooldown = 0;

	public array $modules = [];
	
	public function getName() : string{
		return "quests";
	}

	public function getCurrentQuest() : ?Quest{
		return $this->currentQuest;
	}

	public function hasActiveQuest() : bool{
		return $this->getCurrentQuest() != null;
	}

	public function setActiveQuest(?Quest $quest = null) : void{
		$this->currentQuest = $quest;
	}

	public function getCompletedQuests() : int{
		return $this->completed;
	}

	public function addCompletedQuest() : void{
		$this->completed++;
		$this->setChanged();
	}

	public function getPoints() : int{
		return $this->points;
	}

	public function addPoints(int $value) : void{
		$this->points += $value;
		$this->setChanged();
	}

	public function takePoints(int $value) : void{
		$this->addPoints(-$value);
	}

	public function getCooldown() : int{
		return $this->cooldown;
	}

	public function getLeftoverCooldown() : int{
		return $this->getCooldown() - time();
	}

	public function getFormattedCooldown() : string{
		$seconds = $this->getLeftoverCooldown();

		$hours = floor($seconds / 3600);
		$minutes = floor(((int) ($seconds / 60)) % 60);

		if(strlen((string) $hours) == 1) $hours = "0" . $hours;
		if(strlen((string) $minutes) == 1) $minutes = "0" . $minutes;

		return $hours . "h" . $minutes . "m";
	}

	public function setCooldown($addition = 0) : void{
		$this->cooldown = time() + self::QUEST_COOLDOWN + $addition;
		$this->setChanged();
	}

	public function hasCooldown() : bool{
		return $this->getLeftoverCooldown() >= 0;
	}

	public function getModules() : array{
		return $this->modules;
	}

	public function hasModule(int $number) : bool{
		return in_array($number, $this->getModules());
	}

	public function addModule(int $number) : void{
		$this->modules[] = $number;
		$this->setChanged();
	}

	public function createTables() : void{
		$db = $this->getSession()->getSessionManager()->getDatabase();
		foreach([
			"CREATE TABLE IF NOT EXISTS quest_data(xuid BIGINT(16) NOT NULL UNIQUE, completed INT NOT NULL DEFAULT '0', points INT NOT NULL DEFAULT '0', cooldown INT NOT NULL DEFAULT '0', modules BLOB NOT NULL)",
		] as $query) $db->query($query);
	}

	public function loadAsync() : void{
		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main", "SELECT completed, points, cooldown, modules FROM quest_data WHERE xuid=?", [$this->getXuid()]));
		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null) : void{
		$result = $request->getQuery()->getResult();
		$rows = (array) $result->getRows();
		if(count($rows) > 0){
			$data = array_shift($rows);
			$this->completed = $data["completed"];
			$this->points = $data["points"];
			$this->cooldown = $data["cooldown"];
			$this->modules = unserialize(base64_decode(zlib_decode(hex2bin($data["modules"]))));
		}

		parent::finishLoadAsync($request);
	}

	public function verifyChange() : bool{
		$verify = $this->getChangeVerify();
		return
			$this->getCompletedQuests() !== $verify["completed"] ||
			$this->getPoints() !== $verify["points"] ||
			$this->getCooldown() !== $verify["cooldown"] ||
			$this->getModules() !== $verify["modules"];
	}

	public function saveAsync() : void{
		if(!$this->hasChanged() || !$this->isLoaded()) return;

		$this->setChangeVerify([
			"completed" => $this->getCompletedQuests(),
			"points" => $this->getPoints(),
			"cooldown" => $this->getCooldown(),
			"modules" => $this->getModules()
		]);

		$player = $this->getPlayer();
		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main",
			"INSERT INTO quest_data(xuid, completed, points, cooldown, modules) VALUES(?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE completed=VALUES(completed), points=VALUES(points), cooldown=VALUES(cooldown), modules=VALUES(modules)",
			[$this->getXuid(), $this->getCompletedQuests(), $this->getPoints(), $this->getCooldown(), bin2hex(zlib_encode(base64_encode(serialize($this->getModules())), ZLIB_ENCODING_DEFLATE, 1))]
		));
		$this->newRequest($request, ComponentRequest::TYPE_SAVE);
		parent::saveAsync();
	}

	public function save() : bool{
		if(!$this->hasChanged() || !$this->isLoaded()) return false;

		$xuid = $this->getXuid();
		$completed = $this->getCompletedQuests();
		$points = $this->getPoints();
		$cooldown = $this->getCooldown();
		$modules = bin2hex(zlib_encode(base64_encode(serialize($this->getModules())), ZLIB_ENCODING_DEFLATE, 1));

		$db = $this->getSession()->getSessionManager()->getDatabase();
		$stmt = $db->prepare("INSERT INTO quest_data(xuid, completed, points, cooldown, modules) VALUES(?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE completed=VALUES(completed), points=VALUES(points), cooldown=VALUES(cooldown), modules=VALUES(modules)");
		$stmt->bind_param("iiiis", $xuid, $completed, $points, $cooldown, $modules);
		$stmt->execute();
		$stmt->close();

		return parent::save();
	}

	public function getSerializedData(): array {
		return [
			"completed" => $this->getCompletedQuests(),
			"points" => $this->getPoints(),
			"cooldown" => $this->getCooldown(),
			"modules" => bin2hex(zlib_encode(base64_encode(serialize($this->getModules())), ZLIB_ENCODING_DEFLATE, 1))
		];
	}

	public function applySerializedData(array $data): void {
		$this->completed = $data["completed"];
		$this->points = $data["points"];
		$this->cooldown = $data["cooldown"];
		$this->modules = unserialize(base64_decode(zlib_decode(hex2bin($data["modules"]))));
	}

}