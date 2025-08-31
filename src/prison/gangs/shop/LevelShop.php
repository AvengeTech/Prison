<?php namespace prison\gangs\shop;

use prison\gangs\objects\Gang;

use core\ui\elements\simpleForm\Button;
use core\utils\BlockRegistry;
use core\utils\conversion\LegacyItemIds;
use core\utils\ItemRegistry;
use core\utils\TextFormat;
use Exception;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use TypeError;

class LevelShop{

	public $level;

	public $stock = [];

	public function __construct(int $level, array $stock){
		$this->level = $level;

		$this->stock = $this->setupStock($stock);
	}

	public function setupStock(array $stock) : array{
		$nstock = [];
		foreach($stock as $item => $price){
			$data = explode(":", $item);
			$type = array_shift($data);
			switch($type){
				case "i":
					$id = array_shift($data);
					$count = array_shift($data);

					if (is_null($item = ItemRegistry::findItem($id))) break;
					/** @var Item $item */
					$item->setCount($count);
					$nstock[] = new ShopStock($item, (int) $price);
					break;

				case "ce":
					$item = ItemRegistry::REDEEMABLE_BOOK();
					$extra = array_shift($data);
					$nstock[] = new ShopStock($item, (int) $price, $extra);
					break;

				case "b": //todo
					$item = ItemRegistry::SALE_BOOSTER();
					$extra = array_shift($data);
					$item->setup($extra);
					$nstock[] = new ShopStock($item, (int) $price, $extra);
					break;

			}
		}
		return $nstock;
	}

	public function getLevel() : int{
		return $this->level;
	}

	public function getStock() : array{
		return $this->stock;
	}

	public function getButton(?Gang $gang = null) : Button{
		$level = $this->getLevel();
		return new Button(($gang === null || $gang->getLevel() < $level ? TextFormat::RED : TextFormat::GREEN) . "Level " . $level . " Shop");
	}

}