<?php namespace prison\cells\stores;

use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\player\Player;

class StockManager{
	
	public array $stock = [];

	public function __construct(
		public Store $store,
		string $data
	){
		$this->stock = $this->fromString($data);
	}

	public function getNewStockId() : int{
		$id = 0;
		foreach($this->getStock() as $stock){
			if($stock->getId() > $id) $id = $stock->getId();
		}
		return $id + 1;
	}

	public function getStore() : Store{
		return $this->store;
	}

	public function getMaxStock() : int{
		return 5; //todo
	}

	public function getEmptyStock() : array{
		$stock = [];
		foreach($this->getStock() as $id => $st){
			if($st->getAvailable() == 0) $stock[$id] = $st;
		}
		return $stock;
	}

	public function getFirstStock() : ?Stock{
		$stock = $this->getStock();
		return array_shift($stock);
	}

	public function getStock() : array{
		return $this->stock;
	}

	public function getStockByStock(?Stock $stock = null) : ?Stock{
		if($stock === null) return $stock;
		foreach($this->getStock() as $st){
			if($st->getRuntimeId() == $stock->getRuntimeId()) return $stock;
		}
		return null;
	}

	public function getSingleStock(int $id) : ?Stock{
		return $this->stock[$id] ?? null;
	}

	public function setStock(array $stock) : void{
		$this->stock = $stock;
	}

	public function addStock(Stock $stock) : void{
		$this->stock[$stock->getId()] = $stock;
	}

	public function removeStock(Stock $stock) : void{
		unset($this->stock[$stock->getId()]);
	}

	public function getStockByItem(Item $item) : ?Stock{
		$item = clone $item;
		$item->setCount(1);
		foreach($this->getStock() as $stock){
			if($stock->getItem()->equals($item)) return $stock;
		}
		return null;
	}

	public function swapStock(int $key1, int $key2) : bool{
		$stock = $this->getStock();
		$stock1 = $stock[$key1] ?? null;
		$stock2 = $stock[$key2] ?? null;
		if($stock1 !== null && $stock2 !== null){
			$stock[$key1] = $stock2;
			$stock[$key2] = $stock1;
			$this->setStock($stock);
			$this->getStore()->setChanged();
			return true;
		}
		return false;
	}

	public function stockAll(Player $player) : int{
		$total = 0;
		foreach($this->getStock() as $stock){
			$total += $stock->stock($player);
		}
		return $total;
	}

	public function getTotalStockable(Player $player) : int{
		$count = 0;
		foreach($this->getStock() as $stock){
			$count += $stock->getTotalStockable($player);
		}
		return $count;
	}

	public function fromString(string $data) : array{
		if($data == "") return [];
		$stock = [];

		$data = unserialize(zlib_decode($data));
		$stream = new BigEndianNbtSerializer();
		foreach($data as $id => $st){
			$stock[$id] = new Stock(
				$this,
				$id,

				Item::nbtDeserialize($stream->read($st["item"])->mustGetCompoundTag()),

				$st["totalsold"] ?? 0,
				$st["available"],
				$st["price"],

				$st["description"] ?? "",

				$st["saleType"],
				$st["saleValue"]
			);
		}

		ksort($stock);

		return $stock;
	}

	public function toString() : string{
		$stock = [];
		$sid = 0; //sort time
		foreach($this->getStock() as $st){
			$stock[$sid] = $st->toArray();
			$sid++;
		}
		return zlib_encode(serialize($stock), ZLIB_ENCODING_DEFLATE, 1);
	}

}