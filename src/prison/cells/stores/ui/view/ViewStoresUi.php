<?php namespace prison\cells\stores\ui\view;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\{
	Cell,
	CellHolder
};

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class ViewStoresUi extends SimpleForm{

	public $cell;
	public $holder;

	public $stores = [];

	public function __construct(Player $player, Cell $cell, CellHolder $holder, string $message = "", bool $error = true){
		$this->cell = $cell;
		$this->holder = $holder;
		$sm = $holder->getStoreManager();

		/** @var PrisonPlayer $player */
		foreach($sm->getStores() as $store){
			if($store->isOpen()){
				$this->stores[] = $store;
				$this->addButton(new Button($store->getName() . TextFormat::RESET . TextFormat::DARK_GRAY . PHP_EOL . "Tap to view contents!"));
			}
		}

		parent::__construct(
			"View Stores",
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"You are viewing stores of " . TextFormat::YELLOW . $holder->getName() . ". " . TextFormat::WHITE . PHP_EOL .
			"Select one to view it's contents!"
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

		$store = $sm->getStoreByStore($this->stores[$response]);
		if($store == null){
			$player->showModal(new ViewStoresUi($player, $cell, $holder, "This store is no longer available!"));
			return;
		}
		if(!$store->isOpen()){
			$player->showModal(new ViewStoresUi($player, $cell, $holder, "This store is no longer open! Please select another one"));
			return;
		}
		
		$player->showModal(new ViewStoreUi($player, $cell, $holder, $store));
	}

}