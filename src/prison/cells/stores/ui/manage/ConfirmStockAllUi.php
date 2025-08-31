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

class ConfirmStockAllUi extends ModalWindow{

	public $cell;
	public $store;

	public function __construct(Player $player, Cell $cell, Store $store) {
		/** @var PrisonPlayer $player */
		$this->cell = $cell;
		$this->store = $store;

		parent::__construct(
			"Stock All Stock",
			"Are you sure you would like to stock all of your stores using items from your inventory? You can stock a total of " . TextFormat::YELLOW . $store->getStockManager()->getTotalStockable($player) . TextFormat::WHITE . " items!",
			"Stock All", "Go back"
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

		if($response){
			$count = $store->getStockManager()->stockAll($player);
			$player->showModal(new ManageStoreUi($player, $cell, $store, "Successfully stocked " . $count . " items into your store!", false));
		}else{
			$player->showModal(new ManageStoreUi($player, $cell, $store));
		}
	}

}