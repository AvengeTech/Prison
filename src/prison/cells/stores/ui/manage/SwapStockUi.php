<?php namespace prison\cells\stores\ui\manage;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\Cell;
use prison\cells\stores\{
	Store,
	Stock
};

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class SwapStockUi extends SimpleForm{

	public $cell;
	public $store;
	public $stock;

	public $stonks = [];

	public function __construct(Player $player, Cell $cell, Store $store, Stock $stock, string $message = "", bool $error = true) {
		/** @var PrisonPlayer $player */
		$this->cell = $cell;
		$this->store = $store;
		$this->stock = $stock;

		$stm = $store->getStockManager();

		foreach($stm->getStock() as $st){
			$this->stonks[] = $st;
			$this->addButton(new Button($st->getItem()->getName() . TextFormat::RESET . TextFormat::DARK_GRAY . PHP_EOL . "Tap to " . ($st->getId() == $stock->getId() ? "go back" : "swap!")));
		}

		parent::__construct(
			"Swap Stock",
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"Select which stock you'd like to swap this one with!"
		);
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

		$sc = $this->stonks[$response];
		if($sc->getId() == $stock->getId()){
			$player->showModal(new ManageStockUi($player, $cell, $store));
			return;
		}

		$stm->swapStock($stock->getId(), $sc->getId());
		$player->showModal(new ManageStockUi($player, $cell, $store, "Successfully swapped stock positions!", false));
	}

}