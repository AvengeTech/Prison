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

class ConfirmStockUi extends ModalWindow{

	public $cell;
	public $store;
	public $stock;

	public function __construct(Player $player, Cell $cell, Store $store, Stock $stock) {
		/** @var PrisonPlayer $player */
		$this->cell = $cell;
		$this->store = $store;
		$this->stock = $stock;

		parent::__construct(
			"Stock Cell Stock",
			"Are you sure you would like to refill this stock using items from your inventory? You can stock a total of " . TextFormat::YELLOW . $stock->getTotalStockable($player) . TextFormat::WHITE . " items!",
			"Stock", "Go back"
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

		$stm = $store->getStockManager();
		$stock = $stm->getStockByStock($this->stock);
		if($stock === null){
			$player->showModal(new ManageStoreUi($player, $cell, $store, "This stock no longer exists!"));
			return;
		}

		if($response){
			$count = $stock->stock($player);
			$player->showModal(new EditStockUi($player, $cell, $store, $stock, "Successfully stocked " . $count . " items into your store!", false));
		}else{
			$player->showModal(new EditStockUi($player, $cell, $store, $stock));
		}
	}

}