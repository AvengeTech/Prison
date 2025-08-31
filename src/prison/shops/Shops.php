<?php

namespace prison\shops;

use pocketmine\player\Player;

use prison\Prison;
use prison\shops\commands\{
	AutoSell,
	BlackMarket,
	SellHand,
	SellAll
};
use prison\shops\pieces\{
	Category,
	ShopItem
};

use core\utils\TextFormat;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use prison\PrisonPlayer;

class Shops{

	public array $categories = [];
	public array $itemcache = [];

	public function __construct(public Prison $plugin){
		foreach (Structure::PRICES as $i => $_) {
			$this->categories[$i] = new Category($i);
		}

		$plugin->getServer()->getCommandMap()->registerAll("shops", [
			new AutoSell($plugin, "autosell", "Toggle automatic item selling"),
			new BlackMarket($plugin, "blackmarket", "Open up the Black Market"),
			new SellHand($plugin, "sellhand", "Sell the item you are holding"),
			new SellAll($plugin, "sellall", "Sell all items in your inventory")
		]);
	}

	/** @return Category[] */
	public function getCategories() : array{ return $this->categories; }

	public function getCategoryById(int $id) : ?Category{ return $this->categories[$id] ?? null; }

	public function getShopItem(Item $item) : ?ShopItem{
		$aliases = StringToItemParser::getInstance()->lookupAliases($item);

		foreach($aliases as $alias){
			if(isset($this->itemcache[$alias])) return $this->itemcache[$alias];

			foreach($this->getCategories() as $id => $category){
				if(is_null(($item = $category->getShopItem($alias)))) continue;
				if(!$item->isValid()) continue;

				return $this->itemcache[$alias] = $item;
			}
		}

		return null;
	}

	public function sellDrops(Player $player, array $drops, bool $mined = false): array {
		/** @var PrisonPlayer $player */
		$leftover = [];
		$value = 0;
		$session = $player->getGameSession()->getShops();
		/** @var Item[] $drops */
		foreach ($drops as $drop) {
			$shopitem = $this->getShopItem($drop);
			if ($shopitem == null || $shopitem->canSell() === false) {
				$leftover[] = $drop;
				continue;
			}
			$value += ($shopitem->getSellPrice() * $drop->getCount());
		}
		if ($mined && $session->isActive()) $value *= $session->getBoost();
		$player->addTechits((int) $value);
		return $leftover;
	}

	public function sellHand(Player $player): bool {
		/** @var PrisonPlayer $player */
		$hand = $player->getInventory()->getItemInHand();
		if($hand->isNull()) {
			$player->sendMessage(TextFormat::RI . "You must be holding an item to sell!");
			return false;
		}
		$shopitem = $this->getShopItem($hand);
		if ($shopitem == null || $shopitem->canSell() === false) {
			$player->sendMessage(TextFormat::RI . "This item cannot be sold.");
			return false;
		}

		$value = ($shopitem->getSellPrice() * $hand->getCount());
		$player->getInventory()->removeItem($hand);
		$player->addTechits((int) $value);
		$player->sendMessage(TextFormat::GI . "Sold " . TextFormat::YELLOW . $hand->getCount() . " of " . $hand->getName() . TextFormat::GRAY . " for " . TextFormat::AQUA . number_format($value) . " techits!");
		return true;
	}

	public function sellAll(Player $player): bool {
		/** @var PrisonPlayer $player */
		$size = $player->getInventory()->getSize();

		$sold = 0;
		$price = 0;

		for ($i = 0; $i < $size; $i++) {
			$item = $player->getInventory()->getItem($i);
			$shopitem = $this->getShopItem($item);

			if ($shopitem == null || $shopitem->canSell() === false) continue;

			$sold += $item->getCount();

			$price += ($shopitem->getSellPrice() * $item->getCount());
			$player->getInventory()->removeItem($item);
		}

		if ($sold > 0) {
			$player->sendMessage(TextFormat::GI . "Sold " . TextFormat::YELLOW . number_format($sold) . TextFormat::GRAY . " items for " . TextFormat::AQUA . number_format($price) . " techits!");
			$player->addTechits((int) $price);
			return true;
		}
		$player->sendMessage(TextFormat::RI . "Your inventory has no items available for sale.");
		return false;
	}
}
