<?php namespace prison\shops;

use pocketmine\Server;

use prison\Prison;
use prison\shops\item\SaleBooster;

use core\utils\NewSaveableSession;

class Session extends NewSaveableSession{

	public $boost = 0.0;
	public $length = 0;
	public $started = 0;

	public function load() : void{
		parent::load();

		$xuid = $this->getXuid();

		$db = Prison::getInstance()->getDatabase();
		$stmt = $db->prepare("SELECT * FROM sale_boosts WHERE xuid=?");
		$stmt->bind_param("i", $xuid);
		$stmt->bind_result($x, $boost, $length, $started);
		if($stmt->execute()){
			$stmt->fetch();
		}
		$stmt->close();

		if($x == null) return; //New data

		$this->boost = $boost;
		$this->length = $length;
		$this->started = $started;
	}

	public function isActive(bool $checkboost = true) : bool{
		return (!$checkboost || $this->getBoost() > 0) && $this->getTil() > time();
	}

	public function addBoost(SaleBooster $item) : void{
		if(!$this->isLoaded()) $this->load();
		$this->boost = $item->getMultiplier();
		$this->length = $item->getDuration();
		$this->started = time();
		$this->setChanged();
	}

	public function getBoost() : float{
		if(!$this->isLoaded()) $this->load();
		return !$this->isActive(false) ? 1 : $this->boost;
	}

	public function getLength() : int{
		if(!$this->isLoaded()) $this->load();
		return $this->length;
	}

	public function getStarted() : int{
		if(!$this->isLoaded()) $this->load();
		return $this->started;
	}

	public function getTil() : int{
		return $this->getStarted() + $this->getLength();
	}

	public function save() : void{
		if(!$this->isLoaded() && !$this->isChanged()) return;

		$xuid = $this->getXuid();
		$boost = $this->getBoost();
		$length = $this->getLength();
		$started = $this->getStarted();

		$db = Prison::getInstance()->getDatabase();
		$stmt = $db->prepare("INSERT INTO sale_boosts(xuid, boost, length, started) VALUES(?, ?, ?, ?) ON DUPLICATE KEY UPDATE boost=VALUES(boost), length=VALUES(length), started=VALUES(started)");
		$stmt->bind_param("idii", $xuid, $boost, $length, $started);
		$stmt->execute();
		$stmt->close();

		$this->setChanged(false);
	}

}