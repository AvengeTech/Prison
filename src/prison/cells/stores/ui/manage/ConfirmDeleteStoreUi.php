<?php namespace prison\cells\stores\ui\manage;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\Cell;
use prison\cells\stores\Store;

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class ConfirmDeleteStoreUi extends ModalWindow{

	public $cell;
	public $store;

	public function __construct(Player $player, Cell $cell, Store $store) {
		/** @var PrisonPlayer $player */
		$this->cell = $cell;
		$this->store = $store;

		parent::__construct(
			"Delete Cell Store",
			"Are you sure you would like to delete your store named '" . $store->getName() . "'? This can NOT be undone! (All store items and earnings will automatically be transferred)",
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
		$sm = $hm->getHolderBy($player)->getStoreManager();
		$store = $sm->getStoreByStore($this->store);

		if($response){
			if($store->delete($player)){
				$player->showModal(new ManageStoresUi($player, $cell, "Successfully deleted store and sent earnings to your deposit! Some stock didn't fit in your inventory, check your inbox!", false));
			}else{
				$player->showModal(new ManageStoresUi($player, $cell, "Successfully deleted store and sent earnings to your deposit! Stock sent to your inventory!", false));
			}
		}else{
			$player->showModal(new ManageStoreUi($player, $cell, $store));
		}
	}

}