<?php namespace prison\cells\stores\ui\manage;

use pocketmine\player\Player;
use pocketmine\item\Durable;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\Cell;
use prison\cells\stores\{
	Store,
	Stock
};
use prison\techits\item\TechitNote;
use prison\mysteryboxes\items\KeyNote;
use prison\enchantments\type\Enchantment;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class EditStockUi extends SimpleForm{

	public $cell;
	public $store;
	public $stock;

	public function __construct(Player $player, Cell $cell, Store $store, Stock $stock, string $message = "", bool $error = true) {
		/** @var PrisonPlayer $player */
		$this->cell = $cell;
		$this->store = $store;
		$this->stock = $stock;

		$item = $stock->getItem();
		$ench = $item->getEnchantments();
		$he = $item->hasEnchantments() && ($cnt = count($ench)) > 0;
		$el = "";
		foreach($ench as $e){
			/** @var Enchantment $e */
			$ee = ($ens = Prison::getInstance()->getEnchantments())->getEnchantment($e->getId(), $e->getLevel());
			if($ee !== null) $el .= "- " . $ee->getName() . " " . $ens->getRoman($ee->getLevel()) . PHP_EOL;
		}

		parent::__construct(
			"Manage Store Stock",
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .

			"Item: " . TextFormat::AQUA . "x" . $item->getCount() . " " . $item->getName() . TextFormat::WHITE . PHP_EOL .
			($item instanceof Durable ?
				"Used: " . ($item->getDamage() > 0 ? TextFormat::GREEN . "YES" : TextFormat::RED . "NO") . TextFormat::WHITE . PHP_EOL .
				($item->hasEnchantments() ?
					"Enchantments:" . PHP_EOL . $el . PHP_EOL
				: "")
			: (
				$item instanceof TechitNote ? "Techit value: " . $item->getTechits() . PHP_EOL : (
					$item instanceof KeyNote ?
						"Key value: x" . $item->getWorth() . " " . $item->getType() . PHP_EOL :
						""
					)
				)
			) .


			"Available: " . $stock->getAvailable() . "/" . $stock->getMaxAvailable() . PHP_EOL . 
			"Total sold: " . number_format($stock->getTotalSold()) . PHP_EOL .
			"Base price: " . number_format($stock->getBasePrice()) . ($stock->isSale() ? " (On sale for " . number_format($stock->getFinalPrice()) . ")" . PHP_EOL . "Sale value: " . $stock->getFormattedSale() : "") . PHP_EOL . PHP_EOL .

			"Description: " . $stock->getDescription() . PHP_EOL . PHP_EOL .
			"Tap a stocked item below for more information!"
		);

		$this->addButton(new Button("Restock from inventory"));
		$this->addButton(new Button("Withdraw stock"));
		$this->addButton(new Button("Manage Sale"));
		$this->addButton(new Button("Edit price"));
		$this->addButton(new Button("Edit description"));
		$this->addButton(new Button("Swap stock"));
		$this->addButton(new Button(TextFormat::RED . "Delete stock"));
		
		$this->addButton(new Button("Go back"));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$cell = Prison::getInstance()->getCells()->getCellManager()->getCellByCell($this->cell);
		if(!$cell->isHolder($player)){
			$player->sendMessage(TextFormat::RI . "You no longer have access to this cell.");
			return;
		}

		$sm = $cell->getHolderBy($player)->getStoreManager();
		$store = $sm->getStoreByStore($this->store);
		if($store === null){
			$player->showModal(new ManageStoresUi($player, $cell, "This store no longer exists!"));
			return;
		}

		$stm = $store->getStockManager();
		$stock = $stm->getStockByStock($this->stock);
		if($stock === null){
			$player->showModal(new ManageStockUi($player, $cell, $store, "This stock no longer exists!"));
			return;
		}

		if($response == 0){
			if($stock->getAvailable() >= $stock->getMaxAvailable()){
				$player->showModal(new EditStockUi($player, $cell, $store, $stock, "This item is already fully stocked! You can only have a total of " . Stock::MAX_STACKS . " stacks!"));
				return;
			}
			if($stock->getTotalStockable($player) <= 0){
				$player->showModal(new EditStockUi($player, $cell, $store, $stock, "Your inventory doesn't contain any of this item!"));
				return;
			}
			$player->showModal(new ConfirmStockUi($player, $cell, $store, $stock));
			return;
		}
		if($response == 1){
			$player->showModal(new WithdrawStockUi($player, $cell, $store, $stock));
			return;
		}
		if($response == 2){
			$player->showModal(new StockSaleUi($player, $cell, $store, $stock));
			return;
		}
		if($response == 3){
			if($stock->isSale()){
				$player->showModal(new EditStockUi($player, $cell, $store, $stock, "Please disable your stock sale before modifying the price!"));
				return;
			}
			$player->showModal(new EditStockPriceUi($player, $cell, $store, $stock));
			return;
		}
		if($response == 4){
			$player->showModal(new EditStockDescriptionUi($player, $cell, $store, $stock));
			return;
		}
		if($response == 5){
			if(count($stm->getStock()) <= 1){
				$player->showModal(new EditStockUi($player, $cell, $store, $stock, "You must have at least 2 different items stock to swap!"));
				return;
			}
			$player->showModal(new SwapStockUi($player, $cell, $store, $stock));
			return;
		}
		if($response == 6){
			$player->showModal(new ConfirmDeleteStockUi($player, $cell, $store, $stock));
			return;
		}

		$player->showModal(new ManageStockUi($player, $cell, $store));
	}

}