<?php namespace prison\techits;

use core\session\component\{
	ComponentRequest,
	SaveableComponent
};
use core\session\mysqli\data\MySqlQuery;

class TechitsComponent extends SaveableComponent{

	public int $techits = 0;

	public function getName() : string{
		return "techits";
	}

	public function getTechits() : int{
		return $this->techits;
	}

	public function setTechits(int $value) : void{
		$this->techits = $value;
		$this->setChanged();
	}

	public function addTechits(int $value = 1) : void{
		$this->techits += $value;
		$this->setChanged();
	}

	public function takeTechits(int $value = 1) : void{
		$this->techits = max(0, $this->techits - $value);
		$this->setChanged();
	}

	public function createTables() : void{
		$db = $this->getSession()->getSessionManager()->getDatabase();
		foreach([
			"CREATE TABLE IF NOT EXISTS techits(xuid BIGINT(16) NOT NULL UNIQUE, techits INT NOT NULL DEFAULT 0)",
		] as $query) $db->query($query);
	}

	public function loadAsync() : void{
		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main", "SELECT * FROM techits WHERE xuid=?", [$this->getXuid()]));
		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null) : void{
		$result = $request->getQuery()->getResult();
		$rows = (array) $result->getRows();
		if(count($rows) > 0){
			$data = array_shift($rows);
			$this->techits = $data["techits"];
		}

		parent::finishLoadAsync($request);
	}

	public function verifyChange() : bool{
		$verify = $this->getChangeVerify();
		return $this->getTechits() !== $verify["techits"];
	}

	public function saveAsync() : void{
		if(!$this->hasChanged() || !$this->isLoaded()) return;

		$this->setChangeVerify([
			"techits" => $this->getTechits(),
		]);

		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main", "INSERT INTO techits(xuid, techits) VALUES(?, ?) ON DUPLICATE KEY UPDATE techits=VALUES(techits)", [$this->getXuid(), $this->getTechits()]));
		$this->newRequest($request, ComponentRequest::TYPE_SAVE);
		parent::saveAsync();
	}

	public function save() : bool{
		if(!$this->hasChanged() || !$this->isLoaded()) return false;

		$xuid = $this->getXuid();
		$techits = $this->getTechits();

		$db = $this->getSession()->getSessionManager()->getDatabase();
		$stmt = $db->prepare("INSERT INTO techits(xuid, techits) VALUES(?, ?) ON DUPLICATE KEY UPDATE techits=VALUES(techits)");
		$stmt->bind_param("ii", $xuid, $techits);
		$stmt->execute();
		$stmt->close();

		return parent::save();
	}

	public function getSerializedData(): array {
		return ["techits" => $this->getTechits()];
	}

	public function applySerializedData(array $data): void {
		$this->techits = $data["techits"];
	}

}