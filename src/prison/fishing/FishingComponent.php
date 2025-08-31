<?php namespace prison\fishing;

use pocketmine\entity\Entity;

use prison\fishing\entity\Hook;

use core\session\component\{
	ComponentRequest,
	SaveableComponent
};
use core\session\mysqli\data\MySqlQuery;

class FishingComponent extends SaveableComponent{

	public ?Hook $hook = null;
	public ?Entity $hooked = null;

	public int $catches = 0;

	public function getName() : string{
		return "fishing";
	}

	public function isFishing() : bool{
		if($this->getHook() === null){
			return false;
		}
		return !$this->getHook()->isClosed();
	}

	public function getHook() : ?Hook{
		return $this->hook;
	}

	public function setFishing(?Hook $hook = null) : void{
		$this->hook = $hook;
	}

	public function isHooked() : bool{
		if($this->getHooked() === null){
			return false;
		}
		if($this->getHooked()->isClosed()){
			return false;
		}
		if($this->getHooked()->getPosition()->distance($this->getPlayer()->getPosition()) > 20){
			return false;
		}
		return true;
	}

	public function setHooked(?Entity $entity = null) : void{
		$this->hooked = $entity;
		//$this->getPlayer()->setTargetEntity($entity);
	}

	public function getHooked() : ?Entity{
		return $this->hooked;
	}

	public function getCatches() : int{
		return $this->catches;
	}

	public function addCatch(int $count = 1) : void{
		$this->catches += $count;
		$this->setChanged();
	}

	public function createTables() : void{
		$db = $this->getSession()->getSessionManager()->getDatabase();
		foreach([
			"CREATE TABLE IF NOT EXISTS fishing(xuid BIGINT(16) NOT NULL UNIQUE, catches INT NOT NULL DEFAULT 0)",
		] as $query) $db->query($query);
	}

	public function loadAsync() : void{
		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main", "SELECT * FROM fishing WHERE xuid=?", [$this->getXuid()]));
		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null) : void{
		$result = $request->getQuery()->getResult();
		$rows = $result->getRows();
		if(count($rows) > 0){
			$data = array_shift($rows);
			$this->catches = $data["catches"];
		}

		parent::finishLoadAsync($request);
	}

	public function verifyChange() : bool{
		$verify = $this->getChangeVerify();
		return $this->getCatches() !== $verify["catches"];
	}

	public function saveAsync() : void{
		if(!$this->hasChanged() || !$this->isLoaded()) return;

		$this->setChangeVerify([
			"catches" => $this->getCatches(),
		]);

		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main", "INSERT INTO fishing(xuid, catches) VALUES(?, ?) ON DUPLICATE KEY UPDATE catches=VALUES(catches)", [$this->getXuid(), $this->getCatches()]));
		$this->newRequest($request, ComponentRequest::TYPE_SAVE);
		parent::saveAsync();
	}

	public function save() : bool{
		if(!$this->hasChanged() || !$this->isLoaded()) return false;

		$xuid = $this->getXuid();
		$catches = $this->getCatches();

		$db = $this->getSession()->getSessionManager()->getDatabase();
		$stmt = $db->prepare("INSERT INTO fishing(xuid, catches) VALUES(?, ?) ON DUPLICATE KEY UPDATE catches=VALUES(catches)");
		$stmt->bind_param("ii", $xuid, $catches);
		$stmt->execute();
		$stmt->close();

		return parent::save();
	}

	public function getSerializedData(): array {
		return ["catches" => $this->getCatches()];
	}

	public function applySerializedData(array $data): void {
		$this->catches = $data["catches"];
	}
}