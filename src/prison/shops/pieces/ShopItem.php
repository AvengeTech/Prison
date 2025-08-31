<?php namespace prison\shops\pieces;

use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\item\{
	Item,
	ItemBlock,
    StringToItemParser
};

use prison\shops\uis\TradeUi;
use prison\shops\Structure;

use core\ui\elements\simpleForm\Button;
use core\utils\conversion\LegacyItemIds;
use core\utils\ItemRegistry;
use core\utils\Utils;
use pocketmine\block\utils\ColoredTrait;
use pocketmine\block\utils\DyeColor;

class ShopItem{
	use ColoredTrait;

	public $sell;
	public $buy;

	public $cansell;
	public $canbuy;
	public $maxstack;

	public $item;

	public $customName;

	public $button;
	public $tradeui;

	public $valid = true;
	public $colored = false;

	public function __construct(
		public int|string $id, 
		public int $categoryId
	){
		$data = Structure::PRICES[$categoryId][$this->id];
		$this->sell = $data[0];
		$this->buy = $data[1];
		$this->cansell = isset($data[2]) ? $data[2] : true;
		$this->canbuy = isset($data[3]) ? $data[3] : true;
		$this->maxstack = isset($data[4]) ? $data[4] : 64;
		$this->customName = isset($data[5]) ? $data[5] : "";
		$this->item = StringToItemParser::getInstance()->parse($id);

		$button = new Button((empty($this->customName) ? ($this->item?->getName() ?? "INVALID ITEM") : $this->customName) . PHP_EOL . "Tap to make deal");
		$button->addImage("url", is_null($this->item) ? "" : Structure::getItemImage($this->item));
		$this->button = $button;

		if(is_null($this->item)){
			Utils::dumpVals($id . ";" . implode(";", $data) . " || INVALID ITEM");
			$this->valid = false;
		}
	}

	public function isValid(): bool {
		return $this->valid;
	}

	public function getId(): int|string {
		return $this->id;
	}

	public function getMaxStack() : int{
		return $this->maxstack;
	}

	public function getSellPrice() : int{
		return $this->sell;
	}

	public function getBuyPrice() : int{
		return $this->buy;
	}

	public function canSell() : bool{
		return $this->cansell;
	}

	public function canBuy() : bool{
		return $this->canbuy;
	}

	public function getItem() : Item{
		$item = clone $this->item;
		return $item;
	}

	public function getName() : string{
		return $this->customName == "" ? (($this->colored ? $this->getColor()->getDisplayName() . " " : "") . $this->getItem()->getName()) : $this->customName;
	}

	public function getCategoryId() : int{
		return $this->categoryId;
	}

	public function getButton() : Button{
		return $this->button;
	}

	public function getTradeUi(Player $player) : TradeUi{
		return new TradeUi($player, $this, $this->getCategoryId());
	}

}