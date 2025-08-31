<?php namespace prison\cells;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\stores\StoreManager;

use core\session\mysqli\data\{
	MySqlRequest,
	MySqlQuery
};
use core\user\User;

class CellHolder{

	public StoreManager $storeManager;

	public bool $changed = false;
	public bool $saving = false;

	public function __construct(
		public User $user,
		public Cell $cell,

		public bool $owner = false,

		public int $expiration = 0,
		public int $deposit = 0,

		public bool $storesOpen = false
	){
		$this->storeManager = new StoreManager($user);
	}

	public function tick() : bool{
		return true;
	}

	public function getUser() : User{
		return $this->user;
	}

	public function getPlayer() : ?Player{
		return $this->getUser()->getPlayer();
	}

	public function getName() : string{
		return $this->getUser()->getGamertag();
	}

	public function isOnline() : bool{
		return $this->getUser()->isOnline();
	}

	public function getXuid() : int{
		return $this->getUser()->getXuid();
	}

	public function getCell() : Cell{
		return $this->cell;
	}

	public function setOwner(bool $owner = true) : void{
		$this->owner = $owner;
		$this->setChanged();
	}

	public function isOwner() : bool{
		return $this->owner;
	}

	public function getExpiration() : int{
		return $this->expiration;
	}

	public function setExpiration(int $time) : void{
		$this->expiration = $time;
		$this->setChanged();
	}

	public function getExpirationFormatted(bool $minusweek = false) : string{
		return date("m/d/Y", ($minusweek ? $this->getExpiration() - (86400 * 7) : $this->getExpiration()));
	}

	public function getDeposit() : int{
		return $this->deposit;
	}

	public function addToDeposit(int $amount, ?Player $player = null) : void {
		/** @var PrisonPlayer $player */
		if($player instanceof Player) $player->takeTechits($amount);
		$this->deposit += $amount;

		$this->setChanged();
	}

	public function takeFromDeposit(int $amount, ?Player $player = null) : void {
		/** @var PrisonPlayer $player */
		$amount = ($amount < 0 ? $this->getDeposit() : min($this->getDeposit(), $amount));
		if($player instanceof Player) $player->addTechits($amount);
		$this->deposit -= $amount;

		$this->setChanged();
	}

	public function getCellManager() : CellManager{
		return Prison::getInstance()->getCells()->getCellManager();
	}

	public function areStoresOpen() : bool{
		return $this->storesOpen;
	}

	public function getStoreManager() : StoreManager{
		return $this->storeManager;
	}

	public function hasChanged() : bool{
		return $this->changed;
	}

	public function setChanged(bool $changed = true) : void{
		$this->changed = $changed;
	}

	public function isSaving() : bool{
		return $this->saving;
	}

	public function setSaving(bool $saving = true) : void{
		$this->saving = $saving;
	}

	public function delete() : void{
		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("delete_cell_holder_" . $this->getXuid(), new MySqlQuery("main",
			"DELETE FROM cell_holder_data WHERE holder=?", [$this->getXuid()]
		)), function(MySqlRequest $request) : void{});
	}

	public function save(bool $async = false) : void{
		if(count(($sm = $this->getStoreManager())->getStores()) > 0)
			$this->getStoreManager()->save($async);

		if(!$this->hasChanged()) return;

		if($async){
			$this->setSaving();
			Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("save_cell_holder_" . $this->getXuid(), new MySqlQuery("main",
				"INSERT INTO cell_holder_data(
					holder,
		
					cellid,
					corridor,
					rowid,
		
					isowner,
					expiration,
					deposit,
		
					storesopen
				) VALUES(?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
					isowner=VALUES(isowner),
					expiration=VALUES(expiration),
					deposit=VALUES(deposit),
		
					storesopen=VALUES(storesopen)",
				[
					$this->getXuid(),
					($cell = $this->getCell())->getId(),
					$cell->getCorridor(),
					$cell->getRow(),

					(int) $this->isOwner(),
					$this->getExpiration(),
					$this->getDeposit(),

					(int) $this->areStoresOpen()
				]
			)), function(MySqlRequest $request) : void{
				$this->setChanged(false);
				$this->setSaving(false);
			});
		}else{
			$xuid = $this->getXuid();

			$cell = $this->getCell();
			$cellid = $cell->getId();
			$corridor = $cell->getCorridor();
			$row = $cell->getRow();

			$isowner = (int) $this->isOwner();
			$expiration = $this->getExpiration();
			$deposit = $this->getDeposit();

			$storesOpen = (int) $this->areStoresOpen();

			$db = Prison::getInstance()->getSessionManager()->getDatabase();
			$stmt = $db->prepare(
				"INSERT INTO cell_holder_data(
					holder,
		
					cellid,
					corridor,
					rowid,
		
					isowner,
					expiration,
					deposit,
		
					storesopen
				) VALUES(?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
					isowner=VALUES(isowner),
					expiration=VALUES(expiration),
					deposit=VALUES(deposit),
		
					storesopen=VALUES(storesopen)"
			);

			$stmt->bind_param("iiiiiiii", $xuid, $cellid, $corridor, $row, $isowner, $expiration, $deposit, $storesOpen);
			$stmt->execute();
			$stmt->close();
			$this->setChanged(false);
		}
	}

}