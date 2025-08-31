<?php namespace prison\cells\stores;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\{
	CellManager
};

use core\inbox\Inbox;
use core\inbox\inventory\MessageInventory;
use core\inbox\object\MessageInstance;
use core\session\mysqli\data\{
	MySqlRequest,
	MySqlQuery
};
use core\user\User;
use core\utils\TextFormat;

class Store{

	const LEVEL_RENT = 2000;
	const STARTING_RENT = 4000;
	const STARTING_STOCK = 5;

	public int $rid;
	public StockManager $stockManager;
	public bool $changed = false;
	public bool $saving = false;

	public function __construct(
		public StoreManager $storeManager,
		public int $id,
		public User $user,

		public string $name = "Store",
		public string $description = "",

		public int $totalEarnings = 0,
		public int $earnings = 0,
		public bool $open = true, string $stockdata = ""
	){
		$this->rid = CellManager::newStoreRuntimeId();
		$this->stockManager = new StockManager($this, $stockdata);
	}

	public function getStoreManager() : StoreManager{
		return $this->storeManager;
	}

	public function getRuntimeId() : int{
		return $this->rid;
	}

	public function getId() : int{
		return $this->id;
	}

	public function getUser() : User{
		return $this->user;
	}

	public function isUser(User|Player $player) : bool{
		return $this->getUser()->getXuid() == $player->getXuid();
	}

	public function getName() : string{
		return $this->name;
	}

	public function setName(string $name) : void{
		$this->name = $name;
		$this->setChanged();
	}

	public function getDescription() : string{
		return $this->description;
	}

	public function setDescription(string $description) : void{
		$this->description = $description;
		$this->setChanged();
	}

	public function getTotalEarnings() : int{
		return $this->totalEarnings;
	}

	public function getEarnings() : int{
		return $this->earnings;
	}

	public function addEarnings(int $amount) : void{
		$this->earnings += $amount;
		$this->totalEarnings += $amount;
		$this->setChanged();
	}

	public function withdrawEarnings(int $amount = -1, ?Player $player = null) : void {
		/** @var PrisonPlayer $player */
		$e = $this->getEarnings();
		$amount = ($amount == -1 ? $e : min($e, $amount));
		$cell = $this->getStoreManager()->getCell(false);
		if(!$cell->isOwner($this->getUser()) && $player instanceof Player){
			$player->addTechits($amount);
		}else{
			$cell->getHolderBy($this->getUser())->addToDeposit($amount);
		}
		$this->earnings -= $amount;

		$this->setChanged();
	}

	public function isOpen() : bool{
		return $this->open;
	}

	public function setOpen(bool $open = true) : void{
		$this->open = $open;
	}

	public function getStockManager() : StockManager{
		return $this->stockManager;
	}

	public function getMaxStock() : int{
		return self::STARTING_STOCK;
	}

	public function hasChanged() : bool{
		return $this->changed;
	}

	public function setChanged(bool $changed = true) : void{
		$this->changed = true;
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
			Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("load_cell_store_" . $this->getUser()->getXuid(), new MySqlQuery("main",
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
					$this->getId(), $this->getUser()->getXuid(),
					$this->getName(), $this->getDescription(),
					$this->getTotalEarnings(), $this->getEarnings(),
					(int) $this->isOpen(), $this->getStockManager()->toString()
				]
			)), function(MySqlRequest $request): void {
				$this->setSaving(false);
			});
		}else{
			$sid = $this->getId();
			$xuid = $this->getUser()->getXuid();

			$name = $this->getName();
			$description = $this->getDescription();

			$totalEarnings = $this->getTotalEarnings();
			$earnings = $this->getEarnings();
			$open = (int) $this->isOpen();

			$stock = $this->getStockManager()->toString();

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

			$stmt->bind_param("iissiiis", $sid, $xuid, $name, $description, $totalEarnings, $earnings, $open, $stock);
			$stmt->execute();
			$stmt->close();

			$this->setChanged(false);
		}
	}

	public function delete(Player $player) : void {
		/** @var PrisonPlayer $player */
		$cell = $this->getStoreManager()->getCell(false);
		if(($holder = $cell->getHolderBy($player))->isOwner()){
			$holder->addToDeposit($this->getEarnings());
		}else{
			$player->addTechits($this->getEarnings());
		}
		$inbox = $player->getSession()->getInbox()->getInbox(Inbox::TYPE_HERE);
		$msg = new MessageInstance($inbox, MessageInstance::newId(), time(), 0, "Cell Store Items", "Your inventory was full! So we sent the remaining items of your deleted store to your inbox.", false);
		$leftover = new MessageInventory($msg);
		foreach($this->getStockManager()->getStock() as $stock){
			$amount = $stock->getAvailable();
			$item = $stock->getItem();
			while($amount > 0){
				$item->setCount($sub = min($item->getMaxStackSize(), $amount));
				$amount -= $sub;
				if(!$player->getInventory()->canAddItem($item)){
					$leftover->addItem($item);
				}else{
					$player->getInventory()->addItem($item);
				}
			}
		}
		$msg->setItems($leftover->getContents());

		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("load_cell_store_" . $this->getUser()->getXuid(), new MySqlQuery("main",
			"DELETE FROM cell_store_data WHERE id=? AND holder=?", [$this->getId(), $player->getXuid()]
		)), function(MySqlRequest $request) use($player, $inbox, $msg) : void{
			$this->getStoreManager()->removeStore($this);
			if(count($msg->getItems()) > 0){
				$inbox->addMessage($msg, true);
			}
			if($player->isConnected()) $player->sendMessage(TextFormat::GI . "Store has been deleted!");
		});
	}

}