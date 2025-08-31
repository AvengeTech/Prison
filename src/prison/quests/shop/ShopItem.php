<?php namespace prison\quests\shop;

use pocketmine\player\Player;

use prison\PrisonPlayer;

use core\ui\elements\simpleForm\Button;
use core\utils\conversion\LegacyItemIds;
use core\utils\ItemRegistry;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use prison\item\PouchOfEssence;

class ShopItem{

	public Button $button;

	public function __construct(
		public string $data,
		public int $price
	){
		$button = new Button($this->getName() . PHP_EOL . $this->getPrice() . " points");
		$button->addImage("url", $this->getIcon());
		$this->button = $button;
	}

	public function getData() : string{
		return $this->data;
	}

	public function getPrice() : int{
		return $this->price;
	}

	public function getName(): ?string {
		$data = $this->getData();
		$data = explode(":", $data);
		if($data[0] == "key"){
			return "x" . $data[2] . " " . ucfirst($data[1]) . " Key" . ($data[2] > 1 ? "s" : "");
		}
		if($data[0] == "item"){
			$item = ItemRegistry::findItem(strtoupper($data[1]));
			if (is_null($item)) return null;

			if($item instanceof PouchOfEssence){
				$item->setup("Quest Master", $data[3]);
				$item->init();
			}

			$item->setCount($data[2]);
			return "x" . $item->getCount() . " " . $item->getName() . ($item->getCount() > 1 ? "s" : "");
		}
		return null;
	}

	public function give(Player $player) : bool{
		/** @var PrisonPlayer $player */
		$data = $this->getData();
		$data = explode(":", $data);
		if($data[0] == "key"){
			$count = $data[2];
			$tier = $data[1];

			$session = $player->getGameSession()->getMysteryBoxes();
			$session->addKeys($tier, $count);

			return true;
		}
		if($data[0] == "item"){
			$item = ItemRegistry::findItem($data[1]);
			if (is_null($item)) return false;

			if($item instanceof PouchOfEssence){
				$item->setup("Quest Master", $data[3]);
				$item->init();
			}

			$item->setCount($data[2]);
			$player->getInventory()->addItem($item);
			return true;
		}
		return false;
	}

	public function getButton() : Button{
		return $this->button;
	}

	public function getIcon(): string {
		return "";
	}

}