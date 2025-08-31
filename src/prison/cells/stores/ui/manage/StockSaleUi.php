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
	Dropdown,
	Input
};
use core\utils\TextFormat;

class StockSaleUi extends CustomForm{

	public $cell;
	public $store;
	public $stock;

	public function __construct(
		Player $player, Cell $cell, Store $store, Stock $stock,
		string $message = "", bool $error = true
	) {
		/** @var PrisonPlayer $player */
		parent::__construct("Manage Sale");
		$this->cell = $cell;
		$this->store = $store;
		$this->stock = $stock;

		$type = $stock->getSaleType();

		$this->addElement(new Label(
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"Use the boxes below to manage your sale!"
		));
		$this->addElement(new Dropdown("Sale type", ["No sale", "Percentage", "Amount"], $type + 1));
		$this->addElement(new Input("Sale Value", 27, $stock->getSaleValue()));
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

		$type = $response[1] - 1;
		$value = (int) $response[2];
		if($type == -1) $value = 0;
		switch($type){
			case Stock::SALE_NONE:
				break;
			case Stock::SALE_PERCENT:
				if($value > 100 || $value <= 0){
					$player->showModal(new StockSaleUi($player, $cell, $store, $stock, "Percentage must be within 1-100!"));
					return;
				}
				break;

			case Stock::SALE_AMOUNT:
				if($value > $stock->getBasePrice()){
					$player->showModal(new StockSaleUi($player, $cell, $store, $stock, "Sale value must be less than or equal to your item's base price!"));
					return;
				}
				break;
		}
		$stock->setSaleType($type);
		$stock->setSaleValue($value);
		$player->showModal(new EditStockUi($player, $cell, $store, $stock, "Successfully updated your stock's sale!", false));
	}

}