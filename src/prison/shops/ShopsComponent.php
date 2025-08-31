<?php namespace prison\shops;

use pocketmine\player\Player;

use prison\shops\item\SaleBooster;

use core\session\component\{
	ComponentRequest,
	SaveableComponent
};
use core\session\mysqli\data\MySqlQuery;

class ShopsComponent extends SaveableComponent{

	public float $boost = 0.0;
	public int $length = 0;
	public int $started = 0;
	public int $last = 0;

	public function getName() : string{
		return "shops";
	}

	public function isActive(bool $checkboost = true) : bool{
		return (!$checkboost || $this->getBoost() > 0) && $this->getTil() > time();
	}

	public function addBoost(SaleBooster $item) : void{
		$this->boost = $item->getMultiplier();
		$this->length = $item->getDuration();
		$this->started = time();
		$this->last = $this->started + $this->length;
		$this->setChanged();
	}

	public function getBoost() : float{
		return !$this->isActive(false) ? 1 : $this->boost;
	}

	public function canBoost(): bool {
		return $this->getTimeToNext() <= 0;
	}

	public function getNextBoost(): int {
		return $this->getLast() + (15 * 60);
	}

	public function getTimeToNext(): int {
		return $this->getNextBoost() - time();
	}

	public function getLength() : int{
		return $this->length;
	}

	public function getStarted() : int{
		return $this->started;
	}

	public function getLast(): int {
		return $this->last;
	}

	public function getTil() : int{
		return $this->getStarted() + $this->getLength();
	}

	public function createTables() : void{
		$db = $this->getSession()->getSessionManager()->getDatabase();
		foreach([
				"CREATE TABLE IF NOT EXISTS sale_boosts(xuid BIGINT(16) NOT NULL UNIQUE, boost FLOAT NOT NULL DEFAULT 0, length INT NOT NULL DEFAULT 0, started INT NOT NULL DEFAULT 0, last INT NOT NULL DEFAULT 0)"
		] as $query) $db->query($query);
	}

	public function loadAsync() : void{
		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main", "SELECT * FROM sale_boosts WHERE xuid=?", [$this->getXuid()]));
		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null) : void{
		$result = $request->getQuery()->getResult();
		$rows = (array) $result->getRows();
		if(count($rows) > 0){
			$data = array_shift($rows);
			$this->boost = $data["boost"];
			$this->length = $data["length"];
			$this->started = $data["started"];
			$this->last = $data["last"];
		}

		parent::finishLoadAsync($request);
	}

	public function verifyChange() : bool{
		$verify = $this->getChangeVerify();
		return $this->getBoost() !== $verify["boost"] ||
			$this->getLength() !== $verify["length"] ||
			$this->getStarted() !== $verify["started"] ||
			$this->getLast() !== $verify["last"];
	}

	public function saveAsync() : void{
		if(!$this->hasChanged() || !$this->isLoaded()) return;

		$this->setChangeVerify([
			"boost" => $this->getBoost(),
			"length" => $this->getLength(),
			"started" => $this->getStarted(),
			"last" => $this->getLast()
		]);

		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main",
			"INSERT INTO sale_boosts(xuid, boost, length, started, last) VALUES(?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE boost=VALUES(boost), length=VALUES(length), started=VALUES(started), last=VALUES(last)",
			[$this->getXuid(), $this->getBoost(), $this->getLength(), $this->getStarted(), $this->getLast()]
		));
		$this->newRequest($request, ComponentRequest::TYPE_SAVE);
		parent::saveAsync();
	}

	public function save() : bool{
		if(!$this->hasChanged() || !$this->isLoaded()) return false;

		$xuid = $this->getXuid();
		$boost = $this->getBoost();
		$length = $this->getLength();
		$started = $this->getStarted();
		$last = $this->getLast();

		$db = $this->getSession()->getSessionManager()->getDatabase();
		$stmt = $db->prepare("INSERT INTO sale_boosts(xuid, boost, length, started, last) VALUES(?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE boost=VALUES(boost), length=VALUES(length), started=VALUES(started), last=VALUES(last)");
		$stmt->bind_param("idiii", $xuid, $boost, $length, $started, $last);
		$stmt->execute();
		$stmt->close();

		return parent::save();
	}

	public function getSerializedData(): array {
		return [
			"boost" => $this->getBoost(),
			"length" => $this->getLength(),
			"started" => $this->getStarted(),
			"last" => $this->getLast()
		];
	}

	public function applySerializedData(array $data): void {
		$this->boost = $data["boost"];
		$this->length = $data["length"];
		$this->started = $data["started"];
		$this->last = $data["last"];
	}

}