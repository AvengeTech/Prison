<?php namespace prison\cells\stores\ui\manage;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\Cell;
use prison\cells\stores\{
	Store,
	Stock
};

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Input
};
use core\utils\TextFormat;

class WithdrawStockUi extends CustomForm{

	public $cell;
	public $store;
	public $stock;

	public function __construct(
		Player $player, Cell $cell, Store $store, Stock $stock,
		string $message = "", bool $error = true
	){
		parent::__construct("Withdraw Stock Items");
		/** @var PrisonPlayer $player */
		$this->cell = $cell;
		$this->store = $store;
		$this->stock = $stock;

		$this->addElement(new Label(
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"This stock has " . $stock->getAvailable() . " items available. How many would you like to withdraw?"
		));
		$this->addElement(new Input("Amount", 27, $stock->getAvailable()));
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

		$amount = (int) $response[1];
		if($amount <= 0){
			$player->showModal(new WithdrawStockUi($player, $cell, $store, $stock, "Must withdraw at least one item!"));
			return;
		}

		if($amount > $stock->getAvailable()){
			$player->showModal(new WithdrawStockUi($player, $cell, $store, $stock, "Amount must be less than or equal to amount of stock available!"));
			return;
		}

		$wd = $stock->withdraw($player, $amount);
		$player->showModal(new EditStockUi($player, $cell, $store, $stock, "Successfully withdrew " . $wd . " items from your stock! " . ($wd != $amount ? "(Inventory was full!)" : ""), false));
	}

}