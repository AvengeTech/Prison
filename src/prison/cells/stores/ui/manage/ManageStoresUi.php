<?php namespace prison\cells\stores\ui\manage;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\Cell;
use prison\cells\ui\ManageCellUi;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class ManageStoresUi extends SimpleForm{

	public $cell;
	public $stores = [];

	public function __construct(Player $player, Cell $cell, string $message = "", bool $error = true) {
		/** @var PrisonPlayer $player */
		$this->cell = $cell;
		$hm = $cell->getHolderManager();
		$holder = $hm->getHolderBy($player);
		$sm = $holder->getStoreManager();

		$this->addButton(new Button("Add Store"));
		foreach($sm->getStores() as $store){
			$this->stores[] = $store;
			$this->addButton(new Button($store->getName() . TextFormat::RESET . TextFormat::DARK_GRAY . PHP_EOL . "Tap to manage!"));
		}
		if($cell->isOwner($player)) $this->addButton(new Button("Go back"));

		parent::__construct(
			"Manage Stores",
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"Tap an option below to manage your cell stores!"
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

		if($response == 0){
			if(count($sm->getStores()) > $sm->getMaxStores($player)){
				$player->showModal(new ManageStoresUi($player, $cell, "You already have the max amount of cell stores for your rank!"));
				return;
			}
			if(empty($player->getInventory()->getContents())){
				$player->showModal(new ManageStoresUi($player, $cell, "Your inventory is empty!"));
				return;
			}
			$player->showModal(new AddStoreUi($player, $cell));
			return;
		}
		if($response == count($this->stores) + 1){
			$player->showModal(new ManageCellUi($player, $cell));
			return;
		}

		$store = $sm->getStoreByStore($this->stores[$response - 1]);
		if($store !== null){
			$player->showModal(new ManageStoreUi($player, $cell, $store));
			return;
		}
	}

}