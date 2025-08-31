<?php namespace prison\cells\stores\ui\manage;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\{
	Cell,
	CellHolder,
	stores\Store
};

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class ConfirmAddStoreUi extends ModalWindow{

	public $cell;
	public $store;

	public function __construct(Player $player, Cell $cell, Store $store) {
		/** @var PrisonPlayer $player */
		$this->cell = $cell;
		$this->store = $store;

		parent::__construct(
			"Add Cell Store",
			"Please confirm the following information about your store:" . PHP_EOL . PHP_EOL . 
			"Name: " . $store->getName() . PHP_EOL .
			"Description: " . $store->getDescription() . PHP_EOL .
			"First Stock: " . ($item = ($stk = $store->getStockManager()->getFirstStock())->getItem())->getName() .
				($item->hasEnchantments() && ($cnt = count($item->getEnchantments())) > 1 ? " (" . $cnt . " enchantments)" : "") . PHP_EOL .
			"Price per stock: " . $stk->getBasePrice() . PHP_EOL . PHP_EOL .
			"If all of the information above is correct, press 'Add Store'!",
			"Add Store", "Go back"
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
		$store = $this->store;
		$store->setChanged();
		if($response){
			$fs = $store->getStockManager()->getFirstStock();
			if($fs->stock($player, 1)){
				$sm->addStore($store);
				$player->showModal(new ManageStoresUi($player, $cell, "Successfully added a new store!", false));
			}else{
				$player->showModal(new AddStoreUi($player, $cell, $store->getName(), $store->getDescription(), "Your inventory no longer has this item! Store couldn't be made, please try again!"));
			}
		}else{
			$player->showModal(new AddStoreUi($player, $cell, $store->getName(), $store->getDescription()));
		}
	}

}