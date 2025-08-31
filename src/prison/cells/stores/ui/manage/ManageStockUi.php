<?php namespace prison\cells\stores\ui\manage;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\Cell;
use prison\cells\stores\Store;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class ManageStockUi extends SimpleForm{

	public $cell;
	public $store;
	public $stock = [];

	public function __construct(Player $player, Cell $cell, Store $store, string $message = "", bool $error = true) {
		/** @var PrisonPlayer $player */
		$this->cell = $cell;
		$this->store = $store;

		$this->addButton(new Button("Add new stock"));
		foreach($store->getStockManager()->getStock() as $stock){
			$this->stock[] = $stock;
			$item = $stock->getItem();
			$this->addButton(new Button($item->getName() . TextFormat::RESET . TextFormat::DARK_GRAY . " [" . $stock->getAvailable() . "/" . $stock->getMaxAvailable() . "]" . ($item->hasEnchantments() && ($cnt = count($item->getEnchantments())) > 0 ? " (" . $cnt . " enchantments)" : "") . PHP_EOL . "Tap to manage!"));
		}
		$this->addButton(new Button("Go back"));

		parent::__construct(
			"Manage Store Stock",
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"You are managing stock for store named '" . $store->getName() . "'" . PHP_EOL .
			"Tap a stocked item below for more information!"
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

		if($response == 0){
			if(count($stm->getStock()) >= $stm->getMaxStock()){
				$player->showModal(new ManageStockUi($player, $cell, $store, "Your store can only have a maximum of " . $stm->getMaxStock() . " different items stocked!"));
				return;
			}
			$player->showModal(new AddStockUi($player, $cell, $store));
			return;
		}
		$sr = $this->stock[$response - 1] ?? null;
		if($sr === null){
			$player->showModal(new ManageStoreUi($player, $cell, $store));
			return;
		}
		$stock = $stm->getStockByStock($sr);
		if($stock === null){
			$player->showModal(new ManageStoreUi($player, $cell, $store, "Stock no longer exists!"));
			return;
		}

		$player->showModal(new EditStockUi($player, $cell, $store, $stock));
	}

}