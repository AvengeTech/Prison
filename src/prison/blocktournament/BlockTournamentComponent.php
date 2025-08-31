<?php namespace prison\blocktournament;

use core\session\component\{
	ComponentRequest,
	SaveableComponent
};
use core\session\mysqli\data\MySqlQuery;

use prison\Prison;

class BlockTournamentComponent extends SaveableComponent{

	public int $started = 0;
	public int $wins = 0;
	public int $mined = 0;

	public bool $autoJoin = true;
	public ?Game $lastGame = null;
	
	public function getName() : string{
		return "blocktournament";
	}

	public function getStarted() : int{
		return $this->started;
	}

	public function addStarted() : void{
		$this->started++;
		$this->setChanged();
	}

	public function takeStarted() : void{
		$this->started = max(0, $this->started - 1);
		$this->setChanged();
	}

	public function getWins() : int{
		return $this->wins;
	}

	public function addWin() : void{
		$this->wins++;
		$this->setChanged();
	}

	public function getMined() : int{
		return $this->mined;
	}

	public function addMined(int $amount = 1) : void{
		$this->mined += $amount;
		$this->setChanged();
	}

	public function autoJoins() : bool{
		return $this->autoJoin;
	}

	public function setAutoJoin(bool $auto = true) : void{
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

	public function createTables() : void{
		$db = $this->getSession()->getSessionManager()->getDatabase();
		foreach([
			"CREATE TABLE IF NOT EXISTS block_tournament_data(xuid BIGINT(16) NOT NULL UNIQUE, started INT NOT NULL, wins INT NOT NULL, mined INT NOT NULL)"
		] as $query) $db->query($query);
	}

	public function loadAsync() : void{
		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main", "SELECT started, wins, mined FROM block_tournament_data WHERE xuid=?", [$this->getXuid()]));
		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null) : void{
		$result = $request->getQuery()->getResult();
		$rows = (array) $result->getRows();
		if(count($rows) > 0){
			$data = array_shift($rows);
			$this->started = $data["started"];
			$this->wins = $data["wins"];
			$this->mined = $data["mined"];
		}

		parent::finishLoadAsync($request);
	}

	public function verifyChange() : bool{
		$player = $this->getPlayer();
		$verify = $this->getChangeVerify();
		return $this->getStarted() !== $verify["started"] || $this->getWins() !== $verify["wins"] || $this->getMined() !== $verify["mined"];
	}

	public function saveAsync() : void{
		if(!$this->hasChanged() || !$this->isLoaded()) return;

		$this->setChangeVerify([
			"started" => $this->getStarted(),
			"wins" => $this->getWins(),
			"mined" => $this->getMined(),
		]);

		$player = $this->getPlayer();
		$request = new ComponentRequest($this->getXuid(), $this->getName(),
			new MySqlQuery("main",
				"INSERT INTO block_tournament_data(xuid, started, wins, mined) VALUES(?, ?, ?, ?) ON DUPLICATE KEY UPDATE started=VALUES(started), wins=VALUES(wins), mined=VALUES(mined)",
				[$this->getXuid(), $this->getStarted(), $this->getWins(), $this->getMined()]
			)
		);
		$this->newRequest($request, ComponentRequest::TYPE_SAVE);
		parent::saveAsync();
	}

	public function save() : bool{
		if(!$this->hasChanged() || !$this->isLoaded()) return false;

		$db = $this->getSession()->getSessionManager()->getDatabase();
		$xuid = $this->getXuid();
		$started = $this->getStarted();
		$wins = $this->getWins();
		$mined = $this->getMined();

		$stmt = $db->prepare("INSERT INTO block_tournament_data(xuid, started, wins, mined) VALUES(?, ?, ?, ?) ON DUPLICATE KEY UPDATE started=VALUES(started), wins=VALUES(wins), mined=VALUES(mined)");
		$stmt->bind_param("iiii", $xuid, $started, $wins, $mined);
		$stmt->execute();
		$stmt->close();
		
		return parent::save();
	}

	public function getSerializedData(): array {
		return [
			"started" => $this->getStarted(),
			"wins" => $this->getWins(),
			"mined" => $this->getMined(),
		];
	}

	public function applySerializedData(array $data): void {
		$this->started = $data["started"];
		$this->wins = $data["wins"];
		$this->mined = $data["mined"];
	}

}