<?php namespace prison\cells;

use prison\Prison;

use core\session\component\{
	ComponentRequest,
	SaveableComponent
};
use core\session\mysqli\data\MySqlQuery;

class CellsComponent extends SaveableComponent{

	public array $layouts = [];
	public array $floors = [];

	public function getName() : string{
		return "cells";
	}

	public function getLayouts() : array{
		return $this->layouts;
	}

	public function getFormattedLayouts() : array{
		$layouts = $this->getLayouts();
		foreach($layouts as $key => $layout){
			$layouts[$key] = ucwords(str_replace("_", " ", $layout));
		}
		return $layouts;
	}

	public function hasLayout(string $name) : bool{
		return in_array($name, $this->getLayouts());
	}

	public function addLayout(string $name) : bool{
		if(!$this->hasLayout($name)){
			$this->layouts[] = $name;
			$this->sort(true, false);
			$this->setChanged();
			return true;
		}
		return false;
	}

	public function removeLayout(string $name) : bool{
		foreach($this->layouts as $key => $n){
			if($name == $n){
				unset($this->layouts[$key]);
				$this->sort(true, false);
				$this->setChanged();
				return true;
			}
		}
		return false;
	}

	public function getFloors() : array{
		return $this->floors;
	}

	public function getFormattedFloors() : array{
		$floors = $this->getFloors();
		foreach($floors as $key => $floor){
			$floors[$key] = ucwords(str_replace("_", " ", $floor));
		}
		return $floors;
	}

	public function hasFloor(string $name) : bool{
		return in_array($name, $this->getFloors());
	}

	public function addFloor(string $name) : bool{
		if(!$this->isLoaded()) $this->loadAsync();
		if(!$this->hasFloor($name)){
			$this->floors[] = $name;
			$this->sort(false);
			$this->setChanged();
			return true;
		}
		return false;
	}

	public function removeFloor(string $name) : bool{
		if(!$this->isLoaded()) $this->loadAsync();
		foreach($this->floors as $key => $n){
			if($name == $n){
				unset($this->floors[$key]);
				$this->sort(false);
				$this->setChanged();
				return true;
			}
		}
		return false;
	}

	public function sort(bool $doLayouts = true, bool $doFloors = true) : void{
		if($doLayouts){
			$layouts = $this->getLayouts();
			usort($layouts, function($a, $b){
				return strnatcmp($a, $b);
			});
			$this->layouts = $layouts;
		}
		if($doFloors){
			$floors = $this->getFloors();
			usort($floors, function($a, $b){
				return strnatcmp($a, $b);
			});
			$this->floors = $floors;
		}
	}

	public function getOwnedCell() : ?Cell{
		$cells = Prison::getInstance()->getCells()->getCellManager()->getPlayerCells($player = $this->getPlayer());
		foreach($cells as $cell){
			if($cell->isOwner($player)) return $cell;
		}
		return null;
	}

	public function createTables() : void{
		$db = $this->getSession()->getSessionManager()->getDatabase();
		foreach([
			"CREATE TABLE IF NOT EXISTS cell_holder_data(
				holder BIGINT(16) NOT NULL,

				cellid INT(3) NOT NULL,
				corridor INT(2) NOT NULL,
				rowid TINYINT(1) NOT NULL,

				isowner TINYINT(1) NOT NULL,
				expiration INT NOT NULL,
				deposit INT NOT NULL DEFAULT 0,

				storesopen TINYINT(1) NOT NULL DEFAULT 0,

				PRIMARY KEY (holder, cellid, corridor, rowid)
			)",

			//"DROP TABLE cell_store_data",
			"CREATE TABLE IF NOT EXISTS cell_store_data(
				id INT NOT NULL,
				holder BIGINT(16) NOT NULL,

				name VARCHAR(32) NOT NULL,
				description VARCHAR(255) NOT NULL,

				totalearnings INT NOT NULL DEFAULT 0,
				earnings INT NOT NULL DEFAULT 0,
				open TINYINT(1) NOT NULL DEFAULT 0,

				stock BLOB NOT NULL,

				PRIMARY KEY(id, holder)
			)",

			"CREATE TABLE IF NOT EXISTS cell_player_layouts(
				xuid BIGINT(16) NOT NULL PRIMARY KEY,
				layouts BLOB NOT NULL,
				floors BLOB NOT NULL
			)",

			//"DROP TABLE cell_floor_data",
			"CREATE TABLE IF NOT EXISTS cell_floor_data(
				name VARCHAR(32) NOT NULL PRIMARY KEY,
				blocks BLOB NOT NULL,
				orientation TINYINT(1) NOT NULL
			)",

			//"DROP TABLE cell_layout_data",
			"CREATE TABLE IF NOT EXISTS cell_layout_data(
				name VARCHAR(32) NOT NULL PRIMARY KEY,
				description VARCHAR(255) NOT NULL,
				blocks BLOB NOT NULL,
				orientation TINYINT(1) NOT NULL,
				level INT NOT NULL DEFAULT '1',
				floor VARCHAR(32) DEFAULT ''
			)",
			] as $query) $db->query($query);
	}

	public function loadAsync() : void{
		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main", "SELECT layouts, floors FROM cell_player_layouts WHERE xuid=?", [$this->getXuid()]));
		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null) : void{
		$result = $request->getQuery()->getResult();
		$rows = (array) $result->getRows();
		if(count($rows) > 0){
			$data = array_shift($rows);
			$this->layouts = ($data["layouts"] != " " ? explode(",", $data["layouts"]) : []);
			$this->floors = ($data["floors"] != " " ? explode(",", $data["floors"]) : []);
			$this->sort();
		}

		parent::finishLoadAsync($request);
	}

	public function verifyChange() : bool{
		$verify = $this->getChangeVerify();
		return $this->getLayouts() !== $verify["layouts"] || $this->getFloors() !== $verify["floors"];
	}

	public function saveAsync() : void{
		if(!$this->hasChanged() || !$this->isLoaded()) return;

		$this->setChangeVerify([
			"layouts" => $this->getLayouts(),
			"floors" => $this->getFloors(),
		]);

		$layouts = count($this->getLayouts()) == 0 ? " " : implode(",", $this->getLayouts());
		$floors = count($this->getFloors()) == 0 ? " " : implode(",", $this->getFloors());
		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main",
			"INSERT INTO cell_player_layouts(
				xuid,
				layouts,
				floors
			) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE
				layouts=VALUES(layouts),
				floors=VALUES(floors)",
			[$this->getXuid(), $layouts, $floors]
		));
		$this->newRequest($request, ComponentRequest::TYPE_SAVE);
		parent::saveAsync();
	}

	public function save() : bool{
		if(!$this->hasChanged() || !$this->isLoaded()) return false;

		$xuid = $this->getXuid();
		$layouts = count($this->getLayouts()) == 0 ? " " : implode(",", $this->getLayouts());
		$floors = count($this->getFloors()) == 0 ? " " : implode(",", $this->getFloors());

		$db = $this->getSession()->getSessionManager()->getDatabase();
		$stmt = $db->prepare(
			"INSERT INTO cell_player_layouts(
				xuid,
				layouts,
				floors
			) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE
				layouts=VALUES(layouts),
				floors=VALUES(floors)"
		);

		$stmt->bind_param("iss", $xuid, $layouts, $floors);
		$stmt->execute();
		$stmt->close();

		return parent::save();
	}

	public function getSerializedData(): array {
		$layouts = count($this->getLayouts()) == 0 ? " " : implode(",", $this->getLayouts());
		$floors = count($this->getFloors()) == 0 ? " " : implode(",", $this->getFloors());
		return [
			"layouts" => $layouts,
			"floors" => $floors
		];
	}

	public function applySerializedData(array $data): void {
		$this->layouts = ($data["layouts"] != " " ? explode(",", $data["layouts"]) : []);
		$this->floors = ($data["floors"] != " " ? explode(",", $data["floors"]) : []);
	}

}