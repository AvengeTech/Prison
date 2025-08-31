<?php namespace prison\vaults;

use pocketmine\player\Player;

use core\session\component\{
	ComponentRequest,
	SaveableComponent
};
use core\session\mysqli\data\MySqlQuery;

class VaultsComponent extends SaveableComponent{

	public array $vaults = [];
	public bool $invault = false;
	
	public function getName() : string{
		return "vaults";
	}

	public function getVaultCount() : int{
		$player = $this->getPlayer();
		if(!$player instanceof Player) return 1;

		switch($player->getRank()){
			case "default":
				return 1;
			case "endermite":
				return 2;
			case "blaze":
				return 3;
			case "ghast":
				return 4;
			case "enderman":
				return 5;
			case "wither":
				return 6;
		}
		return 8;
	}

	public function getMaxVaults() : int{
		return 8;
	}

	public function getVaults() : array{
		return $this->vaults;
	}

	public function getVault(int|string $n) : ?Vault{
		foreach($this->getVaults() as $vault){
			if($vault->getId() == $n || $vault->getName() == $n){
				return $vault;
			}
		}
		return null;
	}

	public function inVault() : bool{
		return $this->invault;
	}

	public function setInVault(bool $value = true) : void{
		$this->invault = $value;
	}

	public function createTables() : void{
		$db = $this->getSession()->getSessionManager()->getDatabase();
		foreach([
			"CREATE TABLE IF NOT EXISTS vault_data(xuid BIGINT(16) NOT NULL UNIQUE, vdata LONGBLOB)",
			"CREATE TABLE IF NOT EXISTS vaultdata(xuid BIGINT(16) NOT NULL, vid INT NOT NULL, vdata LONGBLOB, PRIMARY KEY(xuid, vid))",
		] as $query) $db->query($query);
	}

	public function loadAsync() : void{
		$request = new ComponentRequest($this->getXuid(), $this->getName(), [
			new MySqlQuery("main", "SELECT * FROM vaultdata where xuid=?", [$this->getXuid()]),
		]);
		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null) : void{
		$result = $request->getQuery()->getResult();
		$rows = (array) $result->getRows();
		if(count($rows) > 0){ //new
			foreach($rows as $row){
				$id = $row["vid"];
				$data = $row["vdata"];
				$this->vaults[$id] = new Vault($this, $data, $id);
			}

			if(count($this->vaults) < $this->getVaultCount()){
				$missing = [];
				for($i = 1; $i <= $cnt = $this->getVaultCount(); $i++){
					foreach($this->vaults as $id => $vault){
						if($id == $i){
							continue 2;
						}
					}
					$missing[] = $i;
				}
				foreach($missing as $id){
					$this->vaults[$id] = new Vault($this, null, $id);
					$this->setChanged();
				}
			}
		}else{
			for($i = 1; $i <= $cnt = $this->getVaultCount(); $i++){
				$this->vaults[] = new Vault($this, null, $i);
				$this->setChanged();
			}
		}
		parent::finishLoadAsync($request);
	}

	public function verifyChange() : bool{
		$verify = $this->getChangeVerify();
		return $this->getVaults() !== $verify["vaults"];
	}

	public function saveAsync() : void{
		if(!$this->hasChanged() || !$this->isLoaded()) return;

		$this->setChangeVerify([
			"vaults" => $this->getVaults(),
		]);

		$queries = [];
		foreach($this->getVaults() as $vault){
			$queries[] = new MySqlQuery("vault_" . $vault->getId(), "INSERT INTO vaultdata(xuid, vid, vdata) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE vdata=VALUES(vdata)",
				[$this->getXuid(), $vault->getId(), $vault->toString()]
			);
		}

		$request = new ComponentRequest($this->getXuid(), $this->getName(), $queries);
		$this->newRequest($request, ComponentRequest::TYPE_SAVE);
		parent::saveAsync();
	}
	
	public function save() : bool{
		if(!$this->hasChanged() || !$this->isLoaded()) return false;

		$xuid = $this->getXuid();
		$db = $this->getSession()->getSessionManager()->getDatabase();
		$stmt = $db->prepare("INSERT INTO vaultdata(xuid, vid, vdata) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE vdata=VALUES(vdata)");

		foreach($this->getVaults() as $vault){
			if($vault->inventory != null){
				$inventory = $vault->getInventory();
				$vault->setItems($inventory->getContents());
			}

			$id = $vault->getId();
			$data = $vault->toString();
			$stmt->bind_param("iis", $xuid, $id, $data);
			$stmt->execute();
		}

		$stmt->close();
		
		return parent::save();
	}

	public function getSerializedData(): array {
		$vaults = [];
		foreach ($this->getVaults() as $vault) {
			$vaults[] = [
				"vid" => $vault->getId(),
				"data" => $vault->toString()
			];
		}
		return [
			"vaults" => $vaults
		];
	}

	public function applySerializedData(array $data): void {
		$vaults = $data["vaults"];
		foreach ($vaults as $v) {
			$id = $v["vid"];
			$data = $v["data"];
			$this->vaults[$id] = new Vault($this, $data, $id);
		}

		if (count($this->vaults) < $this->getVaultCount()) {
			$missing = [];
			for ($i = 1; $i <= $cnt = $this->getVaultCount(); $i++) {
				foreach ($this->vaults as $id => $vault) {
					if ($id == $i) {
						continue 2;
					}
				}
				$missing[] = $i;
			}
			foreach ($missing as $id) {
				$this->vaults[$id] = new Vault($this, null, $id);
				$this->setChanged();
			}
		}
	}

}