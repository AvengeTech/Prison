<?php namespace prison\cells;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;

use core\Core;
use core\inbox\object\{
	InboxInstance,
	MessageInstance
};
use core\session\mysqli\data\{
	MySqlRequest,
	MySqlQuery
};
use core\user\User;
use core\utils\TextFormat;

class HolderManager{
	
	public array $holders = [];
	public bool $loaded = false;

	public function __construct(public Cell $cell){
		$this->load();
	}

	public function tick() : void{
		if(count($this->getHolders()) > 0){
			$owner = $this->getOwner();
			if($owner === null){
				$least = $this->getEarliestQueued();
				if($least !== null){
					$least->setOwner(true);
					$this->updateHolder($least);
					echo $least->getName() . " made new cell holder due to owner being null!", PHP_EOL;
				}
			}elseif($owner->getExpiration() <= time()/** || strtolower($owner->getName()) == "sn3akpeak"*/){
				echo "cell expired", PHP_EOL;
				foreach(($sm = $owner->getStoreManager())->getStores() as $store){
					$store->setOpen(false);
				}
				$sm->save(true);

				$techits = $owner->getDeposit();
				$this->removeHolder($owner);
				if($owner->isOnline()) {
					/** @var PrisonPlayer $pl */
					$pl = $owner->getPlayer();
					$pl->sendMessage(TextFormat::RI . "Your cell has expired! Your cell deposit of " . TextFormat::AQUA . $techits . " techits " . TextFormat::GRAY . "has been returned. All cell store items remain in your stores until you rent another cell!");
					$pl->addTechits($techits);
				}else{
					$inbox = new InboxInstance($owner->getUser(), "here");
					$inbox->addMessage(new MessageInstance($inbox, MessageInstance::newId(), time(), 0, "Cell available", "You are next up for the cell you queued for!"), true);
				}

				$least = $this->getEarliestQueued();
				if($least !== null){
					$least->setOwner(true);
					$this->updateHolder($least);
					echo $least->getName() . " made new cell holder due to expiration!", PHP_EOL;
				}
			}
		}
	}
	
	public function isLoaded() : bool{
		return $this->loaded;
	}
	
	public function setLoaded(bool $loaded = true) : void{
		$this->loaded = $loaded;
	}

	public function load() : void{
		Prison::getInstance()->getSessionManager()->sendStrayRequest(
			new MySqlRequest("load_holders_" . ($cell = $this->getCell())->getRow() . "_" . $cell->getCorridor() . "_" . $cell->getId(),
				new MySqlQuery("main", "SELECT * FROM cell_holder_data WHERE cellid=? AND corridor=? AND rowid=?", [$cell->getId(), $cell->getCorridor(), $cell->getRow()])
			), function(MySqlRequest $request) : void{
				$rows = $request->getQuery()->getResult()->getRows();
				$xuids = [];
				foreach($rows as $row){
					$xuids[] = $row["holder"];
				}
				Core::getInstance()->getUserPool()->useUsers($xuids, function(array $users) use($rows) : void{
					foreach($rows as $row){
						$this->addHolder(new CellHolder($users[$row["holder"]], $this->getCell(), (bool) $row["isowner"], $row["expiration"], $row["deposit"], $row["storesopen"]));
					}
					if(count($this->getHolders()) > 0){
						$this->getCell()->setActive();
						foreach($this->getHolders() as $holder){
							$holder->getStoreManager()->load();
						}
					}
					$this->setLoaded();
				});
			}
		);
	}

	public function getCell() : Cell{
		return $this->cell;
	}

	public function getHolders() : array{
		return $this->holders;
	}

	public function getOwner() : ?CellHolder{
		foreach($this->getHolders() as $holder){
			if($holder->isOwner()) return $holder;
		}
		return null;
	}

	public function isOwner($player) : bool{
		$holder = $this->getHolderBy($player);
		if($holder !== null){
			return $holder->isOwner();
		}
		return false;
	}

	public function addHolder(CellHolder $holder, bool $cache = true, bool $loadStoreData = false) : bool{
		if($this->getHolderBy($holder->getUser()) !== null) return false;
		if($loadStoreData) $holder->getStoreManager()->load();
		$this->holders[$holder->getXuid()] = $holder;
		if($cache) Prison::getInstance()->getCells()->getCellManager()->hcache[$holder->getXuid()] = $holder;
		$this->getCell()->setActive(true);
		return true;
	}

	public function removeHolder(CellHolder $holder, bool $deleteData = true) : bool{
		$removed = false;
		foreach($this->getHolders() as $k => $h){
			if($holder->getXuid() == $h->getXuid()){
				$holder->getStoreManager()->save();
				if($deleteData) $holder->delete();
				unset($this->holders[$k]);
				unset(Prison::getInstance()->getCells()->getCellManager()->hcache[$holder->getXuid()]);
				$removed = true;
				break;
			}
		}
		if($removed){ //for pushing queue up
			if(!$holder->isOwner()){
				foreach($this->getHolders() as $h){
					if($h->getExpiration() > $holder->getExpiration()){
						$h->setExpiration($h->getExpiration() - (86400 * 7));
						//todo: inbox about place moving up maybe?
					}
				}
			}
			if(empty($this->getHolders())) $this->getCell()->setActive(false);
			return true;
		}
		return false;
	}

	public function updateHolder(CellHolder $holder, bool $cache = true) : bool{
		foreach($this->getHolders() as $k => $h){
			if($holder->getXuid() == $h->getXuid()){
				$this->holders[$k] = $holder;
				if($cache) Prison::getInstance()->getCells()->getCellManager()->hcache[$holder->getXuid()] = $holder;
				return true;
			}
		}
		return false;
	}

	public function getHolderBy(CellHolder|Player|User $player) : ?CellHolder{
		return $this->holders[$player->getXuid()] ?? null;
	}

	public function isHolder(CellHolder|Player|User $player) : bool{
		return isset($this->holders[$player->getXuid()]);
	}

	public function getQueueText() : string{
		$text = "";
		$count = 0;
		$holders = $this->getHolders();
		usort($holders, function($a, $b){
			return strcmp($a->getExpiration(), $b->getExpiration());
		});
		foreach($holders as $holder){
			if(!$holder->isOwner()){
				$count++;
				$text .= TextFormat::GRAY . "- " . TextFormat::YELLOW . $holder->getUser()->getGamertag() . " " . TextFormat::AQUA . "(" . $holder->getExpirationFormatted(true) . ")" . PHP_EOL;
			}
		}
		return $text;
	}

	public function getLatestQueued() : ?CellHolder{
		$holders = $this->getHolders();
		usort($holders, function($a, $b){
			return strcmp($b->getExpiration(), $a->getExpiration());
		});
		return array_shift($holders);
	}

	public function getEarliestQueued() : ?CellHolder{
		$holders = $this->getHolders();
		usort($holders, function($a, $b){
			return strcmp($a->getExpiration(), $b->getExpiration());
		});
		return array_shift($holders);
	}

	public function save(bool $async = false) : void{
		if($async){
			$request = new MySqlRequest("save_holders_cell_" . ($cell = $this->getCell())->getRow() . "_" . $cell->getCorridor() . "_" . $cell->getId(), []);
			foreach($this->getHolders() as $holder){
				if($holder->hasChanged()){
					$request->addQuery(new MySqlQuery("holder_" . $holder->getUser()->getXuid(),
						"INSERT INTO cell_holder_data(
							holder,
				
							cellid,
							corridor,
							rowid,
				
							isowner,
							expiration,
							deposit
						) VALUES(?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
							isowner=VALUES(isowner),
							expiration=VALUES(expiration),
							deposit=VALUES(deposit)",
						[
							$holder->getXuid(),
							
							$cell->getId(),
							$cell->getCorridor(),
							$cell->getRow(),
							
							(int) $holder->isOwner(),
							(int) $holder->getExpiration(),
							$holder->getDeposit()
						]
					));
				}
			}
			Prison::getInstance()->getSessionManager()->sendStrayRequest($request, function(MySqlRequest $request) : void{
				foreach($this->getHolders() as $holder){
					$holder->setChanged(false);
				}
			});
		}else{
			$cell = $this->getCell();
			$cellid = $cell->getId();
			$corridor = $cell->getCorridor();
			$row = $cell->getRow();

			$db = Prison::getInstance()->getSessionManager()->getDatabase();
			$stmt = $db->prepare(
				"INSERT INTO cell_holder_data(
					holder,
		
					cellid,
					corridor,
					rowid,
		
					isowner,
					expiration,
					deposit
				) VALUES(?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
					isowner=VALUES(isowner),
					expiration=VALUES(expiration),
					deposit=VALUES(deposit)"
			);
			foreach($this->getHolders() as $holder){
				if($holder->hasChanged()){
					$xuid = $holder->getXuid();

					$isowner = (int) $holder->isOwner();

					$expiration = (int) $holder->getExpiration();
					$deposit = $holder->getDeposit();

					$stmt->bind_param("iiiiiii", $xuid, $cellid, $corridor, $row, $isowner, $expiration, $deposit);
					$stmt->execute();

					$holder->setChanged(false);
				}
			}
			$stmt->close();
		}
		
		foreach($this->getHolders() as $holder){
			$holder->getStoreManager()->save($async);
		}
	}

}