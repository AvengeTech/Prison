<?php namespace prison\mysteryboxes;

use core\Core;
use pocketmine\player\Player;

use core\session\component\{
	ComponentRequest,
	SaveableComponent
};
use core\session\mysqli\data\MySqlQuery;
use core\utils\TextFormat;
use prison\mysteryboxes\pieces\FilterSetting;
use prison\mysteryboxes\pieces\MysteryBoxFilter;
use prison\Prison;

class MysteryBoxesComponent extends SaveableComponent{

	public array $keys = [
		"iron" => 0,
		"gold" => 0,
		"diamond" => 0,
		"vote" => 0,
		"emerald" => 0,
		"divine" => 0,
	];
	public ?MysteryBoxFilter $filter = null;

	public int $opened = 0;
	
	public function getName() : string{
		return "mysteryboxes";
	}

	public function getAllKeys() : array{
		return $this->keys;
	}

	public function getKeys(string $type) : int{
		return $this->keys[$type] ?? 0;
	}

	public function addKeys(string $type, int $amount = 1) : void{
		$this->keys[$type] += $amount;
		$this->setChanged();
	}

	public function addKeysWithPopup(string $type, int $amount = 1, ?string $sound = null) : void{
		$this->addKeys($type, $amount);
		$player = $this->getPlayer();
		if(!$player instanceof Player) return;
		$color = match($type) {
			"iron" => TextFormat::WHITE,
			"gold" => TextFormat::GOLD,
			"diamond" => TextFormat::AQUA,
			"emerald" => TextFormat::GREEN,
			"vote" => TextFormat::YELLOW,
			"divine" => TextFormat::RED,
		};
		$choices = ["Booyah", "Oh yeah", "Woah", "Cha-ching", "Woop", "Sheeesh"];
		$choice = $choices[mt_rand(0, count($choices) - 1)];
		$player->sendTitle(TextFormat::DARK_PURPLE . $choice . "!", TextFormat::LIGHT_PURPLE . "You found" . $color . " x" . $amount . " " . ucfirst($type) . " Key", 5, 20, 10);

		if($sound !== null) $player->playSound($sound);
	}

	public function takeKeys(string $type, int $amount = 1) : void{
		$this->keys[$type] -= $amount;
		if($this->keys[$type] < 0) $this->keys[$type] = 0;
		$this->setChanged();
	}

	public function getOpened() : int{
		return $this->opened;
	}

	public function addOpened(int $count = 1) : void{
		$this->opened += $count;
		$this->setChanged();
	}

	public function getFilter(bool $changed = false) : MysteryBoxFilter{
		if($changed) $this->setChanged(true);// imma have to do this since all the filter stuff is in a whole different file.

		return $this->filter;
	}

	public function setFilter(MysteryBoxFilter $filter) : self{
		$this->filter = $filter;

		$this->setChanged(true);
		return $this;
	}

	public function createTables() : void{
		$db = $this->getSession()->getSessionManager()->getDatabase();
		foreach([
			"CREATE TABLE IF NOT EXISTS mysterybox_keys(
				xuid BIGINT(16) NOT NULL UNIQUE,
				iron INT NOT NULL DEFAULT 0,
				gold INT NOT NULL DEFAULT 0,
				diamond INT NOT NULL DEFAULT 0,
				vote INT NOT NULL DEFAULT 0,
				emerald INT NOT NULL DEFAULT 0,
				divine INT NOT NULL DEFAULT 0,
				opened INT NOT NULL DEFAULT 0
			)",
			// "DROP TABLE mysterybox_filter",
			"CREATE TABLE IF NOT EXISTS mysterybox_filter(
				xuid BIGINT(16) NOT NULL UNIQUE,
				filterEnabled TINYINT(1) NOT NULL DEFAULT 0,
				inventoryCount INT NOT NULL DEFAULT 0,
				inventoryValue INT NOT NULL DEFAULT 0,
				settings JSON NOT NULL
			)"
		] as $query) $db->query($query);
	}

	public function loadAsync() : void{
		$request = new ComponentRequest($this->getXuid(), $this->getName(), [
			new MySqlQuery("keys", "SELECT * FROM mysterybox_keys WHERE xuid=?", [$this->getXuid()]),
			new MySqlQuery("filter", "SELECT * FROM mysterybox_filter WHERE xuid=?", [$this->getXuid()])
		]);

		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null) : void{
		$keysResult = $request->getQuery("keys")->getResult();
		$keysRows = (array) $keysResult->getRows();
		if(count($keysRows) > 0){
			$keysData = array_shift($keysRows);
			foreach($this->keys as $type => $value){
				$this->keys[$type] = $keysData[$type] ?? 0;
			}
			$this->opened = $keysData["opened"];
		}

		$filterResult = $request->getQuery("filter")->getResult();
		$filterRows =(array) $filterResult->getRows();
		
		$autoClear = ($this->getUser()->getRankHierarchy() < 6);

		if(count($filterRows) > 0){
			$filterData = array_shift($filterRows);
			$settings = json_decode($filterData["settings"], false, 512, JSON_OBJECT_AS_ARRAY);

			$this->filter = new MysteryBoxFilter(
				(bool) $filterData["filterEnabled"],
				$settings,
				$autoClear,
				$filterData["inventoryCount"],
				$filterData["inventoryValue"]
			);
		}else{
			$this->filter = new MysteryBoxFilter(
				false,
				[],
				$autoClear
			);
		}

		parent::finishLoadAsync($request);
	}

	public function verifyChange() : bool{
		$verify = $this->getChangeVerify();

		return (
			$this->keys !== $verify["keys"] || $this->getOpened() !== $verify["opened"] ||
			$this->filter->isEnabled() !== (bool) $verify["isEnabled"] || $this->filter->getSettings() !== $verify["settings"] || $this->getFilter()->getCount() !== $verify["count"]
		);
	}

	public function saveAsync() : void{
		if(!$this->hasChanged() || !$this->isLoaded()) return;

		$this->setChangeVerify([
			"keys" => $this->keys,
			"opened" => $this->getOpened(),
			"isEnabled" => $this->getFilter()->isEnabled(),
			"settings" => $this->getFilter()->getSettings(),
			"count" => $this->getFilter()->getCount()
		]);

		$settings = json_encode($this->getFilter()->getSettings());
		$isEnabled = (int) $this->getFilter()->isEnabled();

		$request = new ComponentRequest($this->getXuid(), $this->getName(), [
			new MySqlQuery("keys",
				"INSERT INTO mysterybox_keys(xuid, iron, gold, diamond, vote, emerald, divine, opened) VALUES(?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE iron=VALUES(iron), gold=VALUES(gold), diamond=VALUES(diamond), vote=VALUES(vote), emerald=VALUES(emerald), divine=VALUES(divine), opened=VALUES(opened)",
				[$this->getXuid(), $this->getKeys("iron"), $this->getKeys("gold"), $this->getKeys("diamond"), $this->getKeys("vote"), $this->getKeys("emerald"), $this->getKeys("divine"), $this->getOpened()]
			),
			new MySqlQuery("filter", 
				"INSERT INTO mysterybox_filter(
					xuid, 
					filterEnabled, 
					inventoryCount, 
					inventoryValue, 
					settings
				) VALUES(?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
				filterEnabled=VALUES(filterEnabled),
				inventoryCount=VALUES(inventoryCount),
				inventoryValue=VALUES(inventoryValue),
				settings=VALUES(settings)",
				[$this->getXuid(), $isEnabled, $this->getFilter()->getCount(), $this->getFilter()->getInventoryValue(), $settings]
			)
		]);
		$this->newRequest($request, ComponentRequest::TYPE_SAVE);
		parent::saveAsync();
	}

	public function save() : bool{
		if(!$this->hasChanged() || !$this->isLoaded()) return false;

		$db = $this->getSession()->getSessionManager()->getDatabase();
		$xuid = $this->getXuid();

		$iron = $this->getKeys("iron");
		$gold = $this->getKeys("gold");
		$diamond = $this->getKeys("diamond");
		$vote = $this->getKeys("vote");
		$emerald = $this->getKeys("emerald");
		$divine = $this->getKeys("divine");

		$opened = $this->getOpened();

		$stmt = $db->prepare("INSERT INTO mysterybox_keys(xuid, iron, gold, diamond, vote, emerald, divine, opened) VALUES(?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE iron=VALUES(iron), gold=VALUES(gold), diamond=VALUES(diamond), vote=VALUES(vote), emerald=VALUES(emerald), divine=VALUES(divine), opened=VALUES(opened)");
		$stmt->bind_param("iiiiiiii", $xuid, $iron, $gold, $diamond, $vote, $emerald, $divine, $opened);
		$stmt->execute();
		$stmt->close();

		$isEnabled = (int) $this->getFilter()->isEnabled();
		$inventoryCount = $this->getFilter()->getCount();
		$inventoryValue = $this->getFilter()->getInventoryValue();

		$settings = json_encode($this->getFilter()->getSettings());
		
		$stmt = $db->prepare("INSERT INTO mysterybox_filter(xuid, filterEnabled, inventoryCount, inventoryValue, settings) VALUES(?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE filterEnabled=VALUES(filterEnabled), inventoryCount=VALUES(inventoryCount), inventoryValue=VALUES(inventoryValue), settings=VALUES(settings)");
		$stmt->bind_param("iiiis", $xuid, $isEnabled, $inventoryCount, $inventoryValue, $settings);
		$stmt->execute();
		$stmt->close();

		return parent::save();
	}

	public function getSerializedData(): array {
		$settings = json_encode($this->getFilter()->getSettings(), JSON_OBJECT_AS_ARRAY);

		return [
			"iron" => $this->getKeys("iron"),
			"gold" => $this->getKeys("gold"),
			"diamond" => $this->getKeys("diamond"),
			"vote" => $this->getKeys("vote"),
			"emerald" => $this->getKeys("emerald"),
			"divine" => $this->getKeys("divine"),
			"opened" => $this->getOpened(),
			"isEnabled" => (int) $this->getFilter()->isEnabled(),
			"inventoryCount" => $this->getFilter()->getCount(),
			"inventoryValue" => $this->getFilter()->getInventoryValue(),
			"settings" => $settings
		];
	}

	public function applySerializedData(array $data): void {
		foreach ($this->keys as $type => $value) {
			$this->keys[$type] = $data[$type] ?? 0;
		}
		$this->opened = $data["opened"];
		
		$autoClear = !($this->getUser()->getRankHierarchy() < 6);
		$settings = json_decode($data["settings"], false, 512);

		$this->filter = new MysteryBoxFilter(
			(bool) $data["isEnabled"],
			$settings,
			$autoClear,
			$data["inventoryCount"],
			$data["inventoryValue"]
		);
	}

}