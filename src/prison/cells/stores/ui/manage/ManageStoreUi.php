<?php namespace prison\cells\stores\ui\manage;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\Cell;
use prison\cells\stores\Store;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class ManageStoreUi extends SimpleForm{

	public $cell;
	public $store;

	public function __construct(Player $player, Cell $cell, Store $store, string $message = "", bool $error = true) {
		/** @var PrisonPlayer $player */
		$this->cell = $cell;
		$this->store = $store;

		parent::__construct(
			"Manage Store",
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"Name: " . $store->getName() . PHP_EOL .
			"Description: " . $store->getDescription() . PHP_EOL .
			"Open: " . ($store->isOpen() ? "YES" : "NO") . PHP_EOL . PHP_EOL .

			"All time earnings: " . number_format($store->getTotalEarnings()) . PHP_EOL . PHP_EOL . 

			"Earnings available: " . number_format($store->getEarnings()) . PHP_EOL .
			"Stock: " . count(($sm = $store->getStockManager())->getStock()) . "/" . $sm->getMaxStock() . " " . (!empty($sm->getEmptyStock()) ? "[" . count($sm->getEmptyStock()) . " need restock!]" : "") . PHP_EOL . PHP_EOL .
			"Tap an option below to edit your store!"
		);

		$this->addButton(new Button(($store->isOpen() ? "Close" : "Open") . " store"));
		$this->addButton(new Button("Restock all from inventory"));
		$this->addButton(new Button("Withdraw earnings"));
		$this->addButton(new Button("Edit name"));
		$this->addButton(new Button("Edit description"));
		$this->addButton(new Button("Add/Edit stock"));
		$this->addButton(new Button("Swap store"));
		$this->addButton(new Button(TextFormat::RED . "Delete store"));
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

		if($response == 0){
			if(!$cell->isOwner($player) && $player->getRank() == "default"){
				$player->showModal(new ManageStoreUi($player, $cell, $store, "Only ranked players can open a cell store while in queue! Purchase one at " . TextFormat::YELLOW . "store.avengetech.net," . TextFormat::RED . " or wait until you're next in queue!"));
				return false;
			}
			$store->setOpen(!$store->isOpen());
			$player->showModal(new ManageStoreUi($player, $cell, $store, "Successfully " . ($store->isOpen() ? "opened" : "closed") . " this store!", false));
			return;
		}
		if($response == 1){
			if($store->getStockManager()->getTotalStockable($player) <= 0){
				$player->showModal(new ManageStoreUi($player, $cell, $store, "Couldn't stock anymore items!"));
				return;
			}
			$player->showModal(new ConfirmStockAllUi($player, $cell, $store));
			return;
		}
		if($response == 2){
			if(($ea = $store->getEarnings()) <= 0){
				$player->showModal(new ManageStoreUi($player, $cell, $store, "This store has no earnings to withdraw!"));
				return;
			}
			$store->withdrawEarnings(-1, $player);
			$player->showModal(new ManageStoreUi($player, $cell, $store, "Successfully withdrew " . TextFormat::AQUA . number_format($ea) . " techits" . TextFormat::GREEN . " from this store" . ($cell->isOwner($player) ? " to your Cell Deposit" : "") . "!", false));
			return;
		}
		if($response == 3){
			$player->showModal(new EditStoreNameUi($player, $cell, $store));
			return;
		}
		if($response == 4){
			$player->showModal(new EditStoreDescriptionUi($player, $cell, $store));
			return;
		}
		if($response == 5){
			$player->showModal(new ManageStockUi($player, $cell, $store));
			return;
		}
		if($response == 6){
			if(count($sm->getStores()) <= 1){
				$player->showModal(new ManageStoreUi($player, $cell, $store, "You must have at least 2 stores setup to swap!"));
				return;
			}
			$player->showModal(new SwapStoreUi($player, $cell, $store));
			return;
		}
		if($response == 7){
			$player->showModal(new ConfirmDeleteStoreUi($player, $cell, $store));
			return;
		}

		$player->showModal(new ManageStoresUi($player, $cell));
	}

}