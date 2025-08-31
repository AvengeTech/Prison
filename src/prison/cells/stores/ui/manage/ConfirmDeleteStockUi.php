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

class ConfirmDeleteStockUi extends ModalWindow{

	public $cell;
	public $store;
	public $stock;

	public function __construct(Player $player, Cell $cell, Store $store, Stock $stock) {
		/** @var PrisonPlayer $player */
		$this->cell = $cell;
		$this->store = $store;
		$this->stock = $stock;

		parent::__construct(
			"Delete Cell Stock",
			"Are you sure you would like to delete your stocked " . $stock->getItem()->getName() . TextFormat::RESET . TextFormat::WHITE . "? This can NOT be undone! (All stock items and will automatically be transferred)",
			"Delete Store", "Go back"
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
			if($stock->delete($player)){
				$player->showModal(new ManageStockUi($player, $cell, $store, "Successfully deleted stock and sent earnings to your deposit! Some stock didn't fit in your inventory, check your inbox!", false));
			}else{
				$player->showModal(new ManageStockUi($player, $cell, $store, "Successfully deleted stock and sent earnings to your deposit! Stock sent to your inventory!", false));
			}
		}else{
			$player->showModal(new ManageStockUi($player, $cell, $store));
		}
	}

}