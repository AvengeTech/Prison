<?php namespace prison\guards;

use pocketmine\math\Vector3;

use prison\Prison;

use core\utils\NewSaveableSession;

// Whole lotta nothing here shane
class Session extends NewSaveableSession{

	public $bins = [];

	public $pathMode = false;
	public $path = null;
	public $lastPos = null;
	public $tap = -1;

	//lost and found bins
	public function load() : void{
		parent::load();

		$xuid = $this->getXuid();
		$db = Prison::getInstance()->getDatabase();
		$stmt = $db->prepare("SELECT * FROM bin_data WHERE xuid=?");
		$stmt->bind_param("i", $xuid);
		$stmt->bind_result($x, $created, $price, $paid, $itemdata);
		if($stmt->execute()){
			while($stmt->fetch()){
				$this->addBin(new Bin($x, $itemdata, $price, (bool) $paid, $created));
			}
		}
		$stmt->close();
	}

	public function getBins() : array{
		if(!$this->isLoaded()) $this->load();
		return $this->bins;
	}

	public function setBin(Bin $bin) : void{
		foreach($this->getBins() as $key => $b){
			if($bin->getTimeCreated() == $b->getTimeCreated()){
				$this->bins[$key] = $bin;
				return;
			}
		}
	}

	public function addBin(Bin $bin) : void{
		if(!$this->isLoaded()) $this->load();
		$this->bins[] = $bin;
	}

	public function removeBin(Bin $bin, bool $delete = true) : void{
		if(!$this->isLoaded()) $this->load();
		foreach($this->getBins() as $key => $b){
			if($bin->getTimeCreated() == $b->getTimeCreated()){
				if($delete) $b->delete();
				unset($this->bins[$key]);
				return;
			}
		}
	}

	public function save() : void{
		if(!$this->isLoaded()) return;
		foreach($this->getBins() as $bin){
			$bin->save();
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
		$this->tap = 20;
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
				$path->points[($id = count($path->points))] = new Point(
					$id, $path->getEndingPoint()->getEnd(),
					$path->getStartingPoint()->getStart()
				);
			}
			$path->save();
			var_dump($path);
			Prison::getInstance()->getGuards()->getPathManager()->addPath($path);
		}

		$this->setPathMode(false);
		$this->setPath();
		$this->setLastPos();
	}

}