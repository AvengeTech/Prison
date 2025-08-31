<?php namespace prison\cells\stores;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\{
	Cell,
};

use core\session\mysqli\data\{
	MySqlRequest,
	MySqlQuery
};
use core\user\User;

class StoreManager{

	const MAX_STORES = 5;
	
	public array $stores = [];
	
	public bool $loaded = false;
	public bool $saving = false;

	public function __construct(public User $user){}
	
	public function isLoaded() : bool{
		return $this->loaded;
	}
	
	public function setLoaded(bool $loaded = true) : void{
		$this->loaded = $loaded;
	}

	public function load() : void{
		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("load_cell_store_" . $this->getUser()->getXuid(), new MySqlQuery("main",
			"SELECT * FROM cell_store_data WHERE holder=?", [$this->getUser()->getXuid()]
		)), function(MySqlRequest $request) : void{
			$rows = $request->getQuery()->getResult()->getRows();
			foreach($rows as $row){
				$this->addStore(
					new Store($this, $row["id"], $this->getUser(), $row["name"], $row["description"], $row["totalearnings"], $row["earnings"], (bool) $row["open"], $row["stock"])
				);
			}
			$this->setLoaded();
		});
	}

	public function getNewStoreId() : int{
		$id = 0;
		foreach($this->getStores() as $store){
			if($store->getId() > $id) $id = $store->getId();
		}
		return $id + 1;
	}

	public function getCell(bool $owns = true) : ?Cell{
		$cells = Prison::getInstance()->getCells()->getCellManager()->getPlayerCells($this->getUser(), $owns);
		if(!empty($cells)) return array_shift($cells);
		return null;
	}

	public function getUser() : User{
		return $this->user;
	}

	public function getStores(bool $open = false) : array{
		if(!$open) return $this->stores;
		$stores = [];
		foreach($this->stores as $id => $store){
			if($store->isOpen()) $stores[$id] = $store;
		}
		return $stores;
	}

	public function setStores(array $stores) : void{
		$this->stores = $stores;
	}

	public function addStore(Store $store) : void{
		$this->stores[$store->getId()] = $store;
	}

	public function removeStore(Store $store) : void{
		unset($this->stores[$store->getId()]);
	}

	public function getStore(int $id) : ?Store{
		return $this->stores[$id] ?? null;
	}

	public function getStoreByRuntime(Store $store) : ?Store{
		foreach($this->getStores() as $st){
			if($store->getRuntimeId() == $st->getRuntimeId()){
				return $st;
			}
		}
		return null;
	}

	public function getStoreByStore(Store $store) : ?Store{
		return $this->getStoreByRuntime($store);
	}

	public function swapStores(int $key1, int $key2) : bool{
		$stores = $this->getStores();
		$store1 = $stores[$key1] ?? null;
		$store2 = $stores[$key2] ?? null;
		if($store1 !== null && $store2 !== null){
			$store1->id = $key2;
			$store2->id = $key1;
			$store1->setChanged();
			$store2->setChanged();
			$stores[$key1] = $store2;
			$stores[$key2] = $store1;
			$this->setStores($stores);
			return true;
		}
		return false;
	}


	public function getMaxStores(Player $player) : int {
		/** @var PrisonPlayer $player */
		$base = self::MAX_STORES;

		$rank = $player->getRank();
		switch($rank){
			default:
			case "default":
				return $base;

		}
	}
	
	public function isSaving() : bool{
		return $this->saving;
	}
	
	public function setSaving(bool $saving = true) : void{
		$this->saving = $saving;
	}

	public function save(bool $async = false) : void{
		if($async){
			$this->setSaving();
			$request = new MySqlRequest("load_cell_store_" . $this->getUser()->getXuid(), []);
			foreach($this->getStores() as $store){
				if($store->hasChanged()){
					$query = new MySqlQuery("main",
						"INSERT INTO cell_store_data(
						id,
						holder,
				
						name,
						description,
				
						totalearnings,
						earnings,
						open,
				
						stock
					) VALUES(?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
						name=VALUES(name),
						description=VALUES(description),
			
						totalearnings=VALUES(totalearnings),
						earnings=VALUES(earnings),
						open=VALUES(open),
			
						stock=VALUES(stock)",
						[
							$store->getId(), $this->getUser()->getXuid(),
							$store->getName(), $store->getDescription(),
							$store->getTotalEarnings(), $store->getEarnings(),
							(int) $store->isOpen(), $store->getStockManager()->toString()
						]
					);
					$request->addQuery($query);
				}
			}

			Prison::getInstance()->getSessionManager()->sendStrayRequest($request, function(MySqlRequest $request) : void{
				$this->setSaving(false);
				foreach($this->getStores() as $store){
					$store->setChanged(false);
				}
			});
		}else{
			$db = Prison::getInstance()->getSessionManager()->getDatabase();
			$stmt = $db->prepare(
				"INSERT INTO cell_store_data(
					id,
					holder,
		
					name,
					description,
		
					totalearnings,
					earnings,
					open,
		
					stock
				) VALUES(?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
					name=VALUES(name),
					description=VALUES(description),
		
					totalearnings=VALUES(totalearnings),
					earnings=VALUES(earnings),
					open=VALUES(open),
		
					stock=VALUES(stock)"
			);
			foreach($this->getStores() as $store){
				if($store->hasChanged()){
					$sid = $store->getId();
					$xuid = $this->getUser()->getXuid();

					$name = $store->getName();
					$description = $store->getDescription();

					$totalearnings = $store->getTotalEarnings();
					$earnings = $store->getEarnings();
					$open = (int) $store->isOpen();

					$stock = $store->getStockManager()->toString();

					$stmt->bind_param("iissiiis", $sid, $xuid, $name, $description, $totalearnings, $earnings, $open, $stock);
					$stmt->execute();

					$store->setChanged(false);
				}
			}
			$stmt->close();
		}
	}

}