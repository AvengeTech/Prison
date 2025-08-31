<?php

namespace prison\enchantments;

use core\session\component\ComponentRequest;
use core\session\component\SaveableComponent;
use core\session\mysqli\data\MySqlQuery;
use prison\item\Essence;

class EssenceComponent extends SaveableComponent{

	private int $essence = 0;
	private array $refineryInventory = [];

	public function getName() : string{ return "essence"; }

	public function getEssence() : int{ return $this->essence; }

	public function addEssence(int $amount) : self{ return $this->setEssence($this->getEssence() + $amount); }

	public function subEssence(int $amount) : self{ return $this->setEssence($this->getEssence() - $amount); }

	public function setEssence(int $amount) : self{
		$this->essence = max(0, $amount);

		$this->setChanged(true);

		return $this;
	}

	public function getRefineryInventory() : array{
		return $this->refineryInventory;
	}

	public function addToInventory(Essence $essence, int $time): void {
		$type = $essence->getType();

		$this->refineryInventory[] = (string) $type . ":" . $essence->getRarity() . ":" . $time;
		$this->refineryInventory = array_values($this->refineryInventory);

		$this->setChanged();
	}

	public function removeFromInventory(int $key): void {
		unset($this->refineryInventory[array_search($key, $this->refineryInventory)]);

		$this->refineryInventory = array_values($this->refineryInventory);

		$this->setChanged();
	}

	public function getFromInventory(int $key) : ?string{
		return $this->refineryInventory[$key] ?? null;
	}

	public function hasTimeLeft(int $key) : bool{
		return $this->getTimeLeft($key) > 0;
	}

	public function getTimeLeft(int $key) : int{
		$itemData = $this->getFromInventory($key);

		if(is_null($itemData)) return 0;

		$itemData = explode(":", $itemData);
		$itemData[2] ??= time();
		$timeLeft = (int) $itemData[2];

		return ($timeLeft - time());
	}

	public function getFormattedTime(int $key) : string{
		$seconds = $this->getTimeLeft($key);

		$minutes = floor(((int) ($seconds / 60)) % 60);

		if(strlen((string) $minutes) == 1) $minutes = "0" . $minutes;

		$seconds = $seconds - (60 * $minutes);

		if(strlen((string) $seconds) == 1) $seconds = "0" . $seconds;

		return $minutes . "min, " . $seconds . "sec";
	}

	public function createTables() : void{
		$db = $this->getSession()->getSessionManager()->getDatabase();

		foreach([
			"CREATE TABLE IF NOT EXISTS essence_data(
				xuid BIGINT(16) NOT NULL UNIQUE,
				essence INT NOT NULL DEFAULT 0,
				refinery_inventory BLOB NOT NULL
			)"
		] as $query) $db->query($query);
	}

	public function loadAsync() : void{
		$request = new ComponentRequest($this->getXuid(), $this->getName(), [
			new MySqlQuery("main", "SELECT * FROM essence_data where xuid=?", [$this->getXuid()]),
		]);
		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null) : void{
		$result = $request->getQuery()->getResult();
		$rows = (array) $result->getRows();

		if(count($rows) > 0){
			$data = array_shift($rows);

			$this->essence = (int) $data["essence"];

			$this->refineryInventory = unserialize(base64_decode(zlib_decode(hex2bin($data["refinery_inventory"]))));
			$this->refineryInventory = array_values($this->refineryInventory); // Just a simple fix to bugged refinery inventories
		}

		parent::finishLoadAsync($request);
	}

	public function verifyChange() : bool{
		$verify = $this->getChangeVerify();

		return count($this->getRefineryInventory()) !== count($verify['refinery_inventory']) || $this->essence !== $verify["essence"];
	}
	
	public function saveAsync() : void{
		if(!$this->hasChanged() || !$this->isLoaded()) return;

		$this->setChangeVerify([
			'essence' => $this->getEssence(),
			'refinery_inventory' => $this->getRefineryInventory()
		]);

		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main",
			"INSERT INTO essence_data(
				xuid, essence, refinery_inventory
			) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE
				essence=VALUES(essence),
				refinery_inventory=VALUES(refinery_inventory)",
			[
				$this->getXuid(), $this->getEssence(), bin2hex(zlib_encode(base64_encode(serialize($this->getRefineryInventory())), ZLIB_ENCODING_DEFLATE, 1))
			]
		));
		
		$this->newRequest($request, ComponentRequest::TYPE_SAVE);
		parent::saveAsync();
	}

	public function save() : bool{
		if(!$this->hasChanged() || !$this->isLoaded()) return false;

		$xuid = $this->getXuid();
		$essence = $this->getEssence();
		$refineryInventory = bin2hex(zlib_encode(base64_encode(serialize($this->getRefineryInventory())), ZLIB_ENCODING_DEFLATE, 1));

		$db = $this->getSession()->getSessionManager()->getDatabase();
		$stmt = $db->prepare(
			"INSERT INTO essence_data(
				xuid, essence, refinery_inventory
			) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE 
			essence=VALUES(essence), refinery_inventory=VALUES(refinery_inventory)"
		);
		$stmt->bind_param("iis", $xuid, $essence, $refineryInventory);
		$stmt->execute();
		$stmt->close();

		return parent::save();
	}

	public function getSerializedData(): array {
		return [
			"essence" => $this->getEssence(),
			"refinery_inventory" => bin2hex(zlib_encode(base64_encode(serialize($this->getRefineryInventory())), ZLIB_ENCODING_DEFLATE, 1))
		];
	}

	public function applySerializedData(array $data): void {
		$this->essence = $data["essence"];
		$this->refineryInventory = unserialize(base64_decode(zlib_decode(hex2bin($data["refinery_inventory"]))));
		$this->refineryInventory = array_values($this->refineryInventory);
	}
}