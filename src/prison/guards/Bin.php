<?php namespace prison\guards;

use pocketmine\inventory\Inventory;
use pocketmine\player\Player;

use pocketmine\item\{
	Item,
	Durable
};

use pocketmine\nbt\{
	BigEndianNbtSerializer as BigEndianNBTStream,
	TreeRoot
};

use prison\Prison;
use prison\PrisonPlayer;
use prison\guards\inventory\BinInventory;
use prison\enchantments\ItemData;

use core\session\mysqli\data\{
	MySqlQuery,
	MySqlRequest
};
use core\user\User;

class Bin{
	
	public array $items = [];

	public int $price = 0;

	public int $time = 0;

	public BinInventory $inventory;

	public bool $changed = false;

	public function __construct(public User $user, mixed $items, int $price = -1, public bool $paid = false, int $time = -1){
		$this->items = is_array($items) ? $items : $this->parseData($items);
		
		$this->inventory = new BinInventory($this);

		$this->price = ($price == -1 ? $this->calculatePrice($this->user->getPlayer()) : $price);

		if($time == -1){
			$this->time = time();
			$this->setChanged();
		}else{
			$this->time = $time;
		}
	}

	public function getUser() : User{
		return $this->user;
	}

	public function getXuid() : int{
		return $this->getUser()->getXuid();
	}

	public function getOriginalItems() : array{
		return $this->items;
	}

	public function parseData(string $data) : array{
		$data = unserialize(zlib_decode($data));
		$stream = new BigEndianNBTStream();
		foreach($data as $slot => $buffer){
			$data[$slot] = Item::nbtDeserialize($stream->read($buffer)->mustGetCompoundTag());
		}
		return $data;
	}

	public function toString() : string{
		$data = [];
		$stream = new BigEndianNBTStream();
		foreach($this->getInventory()->getContents() as $slot => $item){
			$data[$slot] = $stream->write(new TreeRoot($item->nbtSerialize()));
		}
		return zlib_encode(serialize($data), ZLIB_ENCODING_DEFLATE, 1);
	}

	public function getPrice() : int{
		return $this->price;
	}

	public function calculatePrice(?Player $player = null) : int{
		$price = 0;
		foreach($this->getInventory()->getContents() as $item){
			$toadd = 0;
			if($item instanceof Durable){
				$toadd += 100;
				$data = new ItemData($item);
				if($item->hasEnchantments()){
					$toadd += 250;
					foreach($item->getEnchantments() as $ench){
						$en = Prison::getInstance()->getEnchantments()->getEWE($ench);
						$toadd += (100 * $en->getRarity() * $ench->getLevel());
					}
				}
			}else{
				$toadd += 25 * $item->getCount();
			}
			$price += $toadd;
		}
		if($player instanceof Player){
			/** @var PrisonPlayer $player */
			$session = $player->getGameSession()->getRankUp();
			$price = $price * ((ord(strtoupper($session->getRank())) - ord('A') + 1) + (26 * $session->getPrestige()));
		}
		return $price;
	}

	public function isPaid() : bool{
		return $this->paid;
	}

	public function setPaid(bool $paid = true) : void{
		if($this->isPaid() != $paid) $this->setChanged();
		$this->paid = $paid;
	}

	public function getTimeCreated() : int{
		return $this->time;
	}

	public function getTimeFormatted() : string{
		return date("m/d/y", $this->getTimeCreated());
	}

	public function getInventory() : BinInventory{
		return $this->inventory;
	}

	public function hasChanged() : bool{
		return $this->changed;
	}

	public function setChanged(bool $changed = true) : void{
		$this->changed = $changed;
	}

	public function open(?Player $player = null) : bool{
		$player->getNetworkSession()->getInvManager()->getContainerOpenCallbacks()->add(function(int $id, Inventory $inventory) : array{
			return []; //trollface
		});
		$player->setCurrentWindow($this->getInventory());
		return true;
	}

	public function delete() : void{
		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("delete_bin_" . $this->getXuid() . "_" . $this->getTimeCreated(), new MySqlQuery("main",
			"DELETE FROM bin_data WHERE xuid=? AND created=?",
			[
				$this->getXuid(),
				$this->getTimeCreated()
			]
		)), function(MySqlRequest $request) : void{});
	}

}