<?php namespace prison\cells\stores\ui\view;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\{
	Cell,
	CellHolder
};
use prison\cells\stores\Store;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class ViewStoreUi extends SimpleForm{

	public $cell;
	public $holder;
	public $store;

	public $stock = [];

	public function __construct(Player $player, Cell $cell, CellHolder $holder, Store $store, string $message = "", bool $error = true) {
		/** @var PrisonPlayer $player */
		$this->cell = $cell;
		$this->holder = $holder;
		$this->store = $store;
		$sm = $holder->getStoreManager();

		foreach($store->getStockManager()->getStock() as $stock){
			$this->stock[] = $stock;
			$this->addButton($stock->getButton());
		}
		$this->addButton(new Button("Go back"));

		parent::__construct(
			$store->getName(),
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"You are viewing store " . $store->getName() . TextFormat::RESET . TextFormat::WHITE . ", created by " . TextFormat::YELLOW . $holder->getName() . ". " . TextFormat::WHITE . PHP_EOL . PHP_EOL .
			"Description: " . $store->getDescription() . TextFormat::RESET . TextFormat::WHITE . PHP_EOL . PHP_EOL .
			"Select an item to open purchase screen!"
		);
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

		if($response == count($this->stock)){
			$player->showModal(new ViewStoresUi($player, $cell, $holder));
			return;
		}
		$stm = $store->getStockManager();
		$stock = $stm->getStockByStock($this->stock[$response] ?? null);
		if($stock === null){
			$player->showModal(new ViewStoreUi($player, $cell, $holder, $store, "This stock no longer exists!"));
			return;
		}
		if($stock->getAvailable() <= 0){
			$player->showModal(new ViewStoreUi($player, $cell, $holder, $store, "This item is sold out!"));
			return;
		}

		$player->showModal(new StoreStockUi($player, $cell, $holder, $store, $stock));
	}

}