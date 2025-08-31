<?php namespace prison\cells\stores\ui\manage;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\Cell;
use prison\cells\stores\{
	Store,
	Stock
};

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class ConfirmAddStockUi extends ModalWindow{

	public $cell;
	public $store;
	public $stock;

	public function __construct(Player $player, Cell $cell, Store $store, Stock $stock) {
		/** @var PrisonPlayer $player */
		$this->cell = $cell;
		$this->store = $store;
		$this->stock = $stock;

		parent::__construct(
			"Add Cell Stock",
			"You are adding stock to your stored named '" . $store->getName() . TextFormat::RESET . TextFormat::WHITE . "'. Please confirm the following information about your new stock:" . PHP_EOL . PHP_EOL . 
			"Item Name: " . ($item = $stock->getItem())->getName() .
				($item->hasEnchantments() && ($cnt = count($item->getEnchantments())) > 1 ? " (" . $cnt . " enchantments)" : "") . PHP_EOL .
			"Price per stock: " . $stock->getBasePrice() . PHP_EOL . PHP_EOL .
			"If all of the information above is correct, press 'Add Store'!",
			"Add Stock", "Go back"
		);
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$cell = Prison::getInstance()->getCells()->getCellManager()->getCellByCell($this->cell);
		$hm = $cell->getHolderManager();
		if(!$hm->isHolder($player)){
			$player->sendMessage(TextFormat::RI . "You no longer have access to this cell!");
			return;
		}
		$sm = $cell->getHolderBy($player)->getStoreManager();
		$store = $sm->getStoreByStore($this->store);
		if($store === null){
			$player->showModal(new ManageStoresUi($player, $cell, "This store no longer exists!"));
			return;
		}

		$stock = $this->stock;
		if($response){
			if(!$player->getInventory()->contains($stock->getItem())){
				$player->showModal(new AddStockUi($player, $cell, $store, "Your inventory no longer has this item! Please select a new one"));
				return;
			}
			$stock->stock($player, 1);
			$store->getStockManager()->addStock($stock);
			$store->setChanged();
			$player->showModal(new ManageStockUi($player, $cell, $store, "Successfully added new stock to your store!", false));
		}else{
			$player->showModal(new AddStockUi($player, $cell, $store, $stock->getDescription()));
		}
	}

}