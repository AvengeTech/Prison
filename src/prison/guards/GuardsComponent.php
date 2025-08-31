<?php namespace prison\guards;

use pocketmine\math\Vector3;
use pocketmine\player\Player;

use core\session\component\{
	ComponentRequest,
	SaveableComponent
};
use core\session\mysqli\data\MySqlQuery;

use prison\Prison;

class GuardsComponent extends SaveableComponent{

	public int $ticks = 0;
	
	public array $bins = [];

	public bool $pathMode = false;
	public ?Path $path = null;
	public ?Vector3 $lastPos = null;
	public int $tap = -1;

	public function getName() : string{
		return "guards";
	}

	public function getBins() : array{
		return $this->bins;
	}

	public function setBin(Bin $bin) : void{
		foreach($this->getBins() as $key => $b){
			if($bin->getTimeCreated() == $b->getTimeCreated()){
				$this->bins[$key] = $bin;
				$this->setChanged();
				return;
			}
		}
	}

	public function addBin(Bin $bin) : void{
		$this->bins[] = $bin;
		$this->setChanged();
	}

	public function removeBin(Bin $bin, bool $delete = true) : void{
		if(!$this->isLoaded()) $this->loadAsync();
		foreach($this->getBins() as $key => $b){
			if($bin->getTimeCreated() == $b->getTimeCreated()){
				if($delete) $b->delete();
				unset($this->bins[$key]);
				$this->setChanged();
				return;
			}
		}
	}

	//path setting stuff
	public function tick() : void{
		if($this->tap > 0)
			$this->tap--;
	}

	public function inPathMode() : bool{
		return $this->pathMode;
	}

	public function setPathMode(bool $set = true) : void{
		$this->pathMode = $set;
	}

	public function getPath() : ?Path{
		return $this->path;
	}

	public function setPath(?Path $path = null) : void{
		$this->path = $path;
	}

	public function getLastPos() : ?Vector3{
		return $this->lastPos;
	}

	public function setLastPos(?Vector3 $pos = null) : void{
		$this->lastPos = $pos;
	}

	public function canTap() : bool{
		return $this->tap < 1;
	}

	public function setLastTap() : void{
		$this->tap = 4;
	}

	public function addPoint(Vector3 $pos) : bool{
		if(!$this->inPathMode()) return false;

		if($this->getLastPos() == null){
			$this->setLastPos(($pos = $pos->add(0.5, 0, 0.5)));
			return false;
		}

		$point = new Point(count($this->getPath()->getPoints()), $this->lastPos, $pos);
		$this->getPath()->points[$point->getId()] = $point;

		$this->setLastPos($pos);
		$this->setLastTap();
		return true;
	}

	public function reset(bool $save = true) : void{
		if($save){
			$path = $this->getPath();
			if($path->doesLoop()){
				$id = count($path->points);
				$path->points[$id] = new Point(
					(int)$id,
					$path->getEndingPoint()->getEnd(),
					$path->getStartingPoint()->getStart()
				);
			}
			$path->save();
			Prison::getInstance()->getGuards()->getPathManager()->addPath($path);
		}

		$this->setPathMode(false);
		$this->setPath();
		$this->setLastPos();
	}

	public function createTables() : void{
		$db = $this->getSession()->getSessionManager()->getDatabase();
		foreach([
			"CREATE TABLE IF NOT EXISTS bin_data(
				xuid BIGINT(16) NOT NULL,
				created INT NOT NULL,
				price INT NOT NULL,
				paid TINYINT(1) NOT NULL DEFAULT 0,
				items BLOB NOT NULL,
				PRIMARY KEY(xuid, created)
			)",
		] as $query) $db->query($query);
	}

	public function loadAsync() : void{
		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main", "SELECT * FROM bin_data WHERE xuid=?", [$this->getXuid()]));
		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null) : void{
		$result = $request->getQuery()->getResult();
		$rows = (array) $result->getRows();
		foreach($rows as $row){
			$this->bins[] = new Bin($this->getUser(), $row["items"], $row["price"], (bool) $row["paid"], $row["created"]);
		}

		parent::finishLoadAsync($request);
	}

	public function saveAsync() : void{
		if(!$this->isLoaded()) return;

		$player = $this->getPlayer();
		$request = new ComponentRequest($this->getXuid(), $this->getName(), []);
		foreach($this->getBins() as $bin){
			if($bin->hasChanged()){
				$request->addQuery(new MySqlQuery(
					$bin->getTimeCreated(),
					"INSERT INTO bin_data(xuid, created, price, paid, items) VALUES(?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE paid=VALUES(paid), items=VALUES(items);",
					[$this->getXuid(), $bin->getTimeCreated(), $bin->getPrice(), (int) $bin->isPaid(), $bin->toString()]
				));
				$bin->setChanged(false);
			}
		}
		$this->newRequest($request, ComponentRequest::TYPE_SAVE);
		parent::saveAsync();
	}

	public function save() : bool{
		if(!$this->isLoaded()) return false;

		$xuid = $this->getXuid();
		$db = $this->getSession()->getSessionManager()->getDatabase();
		$stmt = $db->prepare("INSERT INTO bin_data(xuid, created, price, paid, items) VALUES(?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE paid=VALUES(paid), items=VALUES(items);");
		foreach($this->getBins() as $bin){
			if($bin->hasChanged()){
				$created = $bin->getTimeCreated();
				$price = $bin->getPrice();
				$paid = (int) $bin->isPaid();
				$items = $bin->toString();
				$stmt->bind_param("iiiis", $xuid, $created, $price, $paid, $items);
				$stmt->execute();
				$bin->setChanged(false);
			}
		}
		$stmt->close();

		return parent::save();
	}

	public function getSerializedData(): array {
		$bins = [];
		foreach ($this->getBins() as $bin) {
			$bins[] = [
				"created" => $bin->getTimeCreated(),
				"price" => $bin->getPrice(),
				"paid" => (int) $bin->isPaid(),
				"items" => $bin->toString()
			];
		}
		return [
			"bins" => $bins
		];
	}

	public function applySerializedData(array $data): void {
		foreach ($data["bins"] as $bin) {
			$this->bins[] = new Bin($this->getUser(), $bin["items"], $bin["price"], (bool) $bin["paid"], $bin["created"]);
		}
	}

}