<?php namespace prison\vaults;

use pocketmine\data\SavedDataLoadingException;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\{
	BigEndianNbtSerializer,
	TreeRoot
};
use pocketmine\player\Player;

use prison\vaults\inventory\VaultInventory;

class Vault{

	const MAX_VAULT_ITEMS = 54;
	
	public int $id;
	public string $name;

	public array $init_items = [];

	public VaultInventory $inventory;

	public function __construct(public ?VaultsComponent $component = null, ?string $data = null, int $id = -1){
		if($data != null){
			try {
			$data = $this->parseData($data);
			$this->name = $data["name"];
			$this->init_items = $data["items"];

			$this->id = ($id == -1 ? $data["id"] : $id);
			} catch (SavedDataLoadingException $e) {
				$this->name = "Vault #" . $id;
				$this->id = $id;
			}
		}else{
			$this->name = "Vault #" . $id;
			$this->id = $id;
		}

		$this->inventory = new VaultInventory($this);
	}

	public function parseData(string $data) : array{
		$data = unserialize(zlib_decode($data));
		$stream = new BigEndianNbtSerializer();
		foreach($data["items"] as $slot => $buffer){
			try {
				$data["items"][$slot] = Item::nbtDeserialize($stream->read($buffer)->mustGetCompoundTag());
			} catch (SavedDataLoadingException $e) {
				$data["items"][$slot] = VanillaItems::AIR();
			}
		}
		return $data;
	}

	public function toString() : string{
		$data = [
			"id" => $this->getId(),
			"name" => $this->getName(),
			"items" => []
		];
		$stream = new BigEndianNbtSerializer();
		foreach($this->getItems() as $slot => $item){
			$data["items"][$slot] = $stream->write(new TreeRoot($item->nbtSerialize()));
		}
		return zlib_encode(serialize($data), ZLIB_ENCODING_DEFLATE, 1);
	}

	public function __toString() : string{
		return $this->toString();
	}

	public function getComponent() : ?VaultsComponent{
		return $this->component;
	}

	public function getId() : int{
		return $this->id;
	}

	public function getName() : string{
		return $this->name;
	}

	public function setName(string $name) : void{
		$this->name = $name;
	}

	public function getInitialItems() : array{
		return $this->init_items;
	}

	public function getItems() : array{
		return $this->getInventory()->getContents();
	}

	public function getItem(int $slot) : ?Item{
		return $this->getInventory()->getItem($slot);
	}

	public function setItems(array $items) : void{
		$this->getInventory()->setContents($items);
	}

	public function getInventory() : VaultInventory{
		return $this->inventory;
	}

	public function isPlayerAccessible() : bool{
		return $this->getComponent()->getVaultCount() >= $this->getId();
	}

	public function open(?Player $player = null) : bool{
		if($player == null) $player = $this->getComponent()->getPlayer();
		$player->getNetworkSession()->getInvManager()->getContainerOpenCallbacks()->add(function(int $id, Inventory $inventory) : array{
			return []; //trollface
		});
		$player->setCurrentWindow($this->getInventory());
		return true;
	}

}