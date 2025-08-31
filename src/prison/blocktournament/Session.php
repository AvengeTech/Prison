<?php namespace prison\blocktournament;

use prison\Prison;

use core\utils\NewSaveableSession;

/**
 * @deprecated 1.9.0
 */
class Session extends NewSaveableSession{

	public $started = 0;
	public $wins = 0;
	public $mined = 0;

	public $autoJoin = true;
	public $lastGame = null;

	public function load() : void{
		parent::load();

		$db = Prison::getInstance()->getDatabase();
		$xuid = $this->getXuid();

		$stmt = $db->prepare("SELECT started, wins, mined FROM block_tournament_data WHERE xuid=?");
		$stmt->bind_param("i", $xuid);
		$stmt->bind_result($started, $wins, $mined);
		if($stmt->execute()){
			$stmt->fetch();
		}
		$stmt->close();

		if($started == null) return;

		$this->started = $started;
		$this->wins = $wins;
		$this->mined = $mined;
	}

	public function getStarted() : int{
		if(!$this->isLoaded()) $this->load();
		return $this->started;
	}

	public function addStarted() : void{
		if(!$this->isLoaded()) $this->load();
		$this->started++;
	}

	public function takeStarted() : void{
		if(!$this->isLoaded()) $this->load();
		$this->started = max(0, $this->started - 1);
	}

	public function getWins() : int{
		if(!$this->isLoaded()) $this->load();
		return $this->wins;
	}

	public function addWin() : void{
		if(!$this->isLoaded()) $this->load();
		$this->wins++;	
	}

	public function getMined() : int{
		if(!$this->isLoaded()) $this->load();
		return $this->mined;
	}

	public function addMined(int $amount = 1) : void{
		if(!$this->isLoaded()) $this->load();
		$this->mined += $amount;
	}

	public function autoJoins() : bool{
		//if(!$this->isLoaded()) $this->load();
		return $this->autoJoin;
	}

	public function setAutoJoin(bool $auto = true) : void{
		//if(!$this->isLoaded()) $this->load();
		$this->autoJoin = $auto;
	}

	public function inGame() : bool{
		return Prison::getInstance()->getBlockTournament()->getGameManager()->getPlayerGame($this->getPlayer()) !== null;
	}

	public function getLastGame() : ?Game{
		return $this->lastGame;
	}

	public function setLastGame(Game $game) : Game{
		return $this->lastGame = $game;
	}

	public function save() : void{
		if(!$this->isLoaded()) return;

		$db = Prison::getInstance()->getDatabase();
		$xuid = $this->getXuid();

		$started = $this->getStarted();
		$wins = $this->getWins();
		$mined = $this->getMined();

		$stmt = $db->prepare("INSERT INTO block_tournament_data(xuid, started, wins, mined) VALUES(?, ?, ?, ?) ON DUPLICATE KEY UPDATE started=VALUES(started), wins=VALUES(wins), mined=VALUES(mined)");
		$stmt->bind_param("iiii", $xuid, $started, $wins, $mined);
		$stmt->execute();
		$stmt->close();
	}

}