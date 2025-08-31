<?php namespace prison\cells\stores\ui\view;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\{
	Cell,
	CellHolder
};
use prison\cells\stores\{
	Store,
	Stock
};

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class PurchaseConfirmUi extends ModalWindow{

	public $cell;
	public $holder;
	public $store;
	public $stock;
	public $amount;

	public function __construct(
		Player $player, Cell $cell, CellHolder $holder, Store $store, Stock $stock,
		int $amount = 1
	) {
		/** @var PrisonPlayer $player */
		parent::__construct(
			"Confirm Purchase Item",
			"Are you sure you would like to purchase x" . $amount . " " . $stock->getItem()->getName() . TextFormat::RESET . TextFormat::WHITE . " from this store for " . TextFormat::AQUA . number_format($stock->getFinalPrice($amount)) . " techits?",
			"Purchase", "Go back"
		);

		$this->cell = $cell;
		$this->holder = $holder;
		$this->store = $store;
		$this->stock = $stock;
		$this->amount = $amount;
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$cell = Prison::getInstance()->getCells()->getCellManager()->getCellByCell($this->cell);
		$holder = $this->holder;
		if(!$cell->isHolder($holder)){
			$player->sendMessage(TextFormat::RI . "This player is no longer a holder of this cell!");
			return;
		}
		$sm = $cell->getHolderBy($holder)->getStoreManager();
		$store = $sm->getStoreByStore($this->store);
		if($store === null){
			$player->showModal(new ViewStoresUi($player, $cell, $holder, "This store no longer exists!"));
			return;
		}
		if(!$store->isOpen()){
			$player->showModal(new ViewStoresUi($player, $cell, $holder, "This store is no longer open! Please select another one"));
			return;
		}
		$stm = $store->getStockManager();
		$stock = $stm->getStockByStock($this->stock);
		if($stock === null){
			$player->showModal(new ViewStoreUi($player, $cell, $holder, $store, "This stock no longer exists!"));
			return;
		}

		if($response){
			if($stock->getAvailable() <= 0){
				$player->showModal(new ViewStoreUi($player, $cell, $holder, $store, "This item is sold out!"));
				return;
			}

			$amount = $this->amount;
			if($amount > $stock->getAvailable()){
				$player->showModal(new StoreStockUi($player, $cell, $holder, $store, $stock, "Please enter a smaller number! This store doesn't have that many items stocked."));
				return;
			}

			if($player->getTechits() < $stock->getFinalPrice($amount)){
				$player->showModal(new StoreStockUi($player, $cell, $holder, $store, $stock, "You cannot afford this many items!"));
				return;
			}

			$stock->buy($player, $amount);
			$player->showModal(new ViewStoreUi($player, $cell, $holder, $store, "Successfully purchased x" . $amount . " " . $stock->getItem()->getName() . TextFormat::RESET . TextFormat::GREEN . " from this store!", false));
		}else{
			$player->showModal(new ViewStoreUi($player, $cell, $holder, $store));
		}
	}
}