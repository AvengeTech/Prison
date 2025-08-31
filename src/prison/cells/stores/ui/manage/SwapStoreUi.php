<?php namespace prison\cells\stores\ui\manage;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\Cell;
use prison\cells\stores\Store;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class SwapStoreUi extends SimpleForm{

	public $cell;
	public $store;
	public $stores = [];

	public function __construct(Player $player, Cell $cell, Store $store, string $message = "", bool $error = true) {
		/** @var PrisonPlayer $player */
		$this->cell = $cell;
		$this->store = $store;
		$hm = $cell->getHolderManager();
		$holder = $hm->getHolderBy($player);
		$sm = $holder->getStoreManager();

		foreach($sm->getStores() as $st){
			$this->stores[] = $st;
			$this->addButton(new Button($st->getName() . PHP_EOL . "Tap to " . ($st->getId() == $store->getId() ? "go back" : "swap!")));
		}

		parent::__construct(
			"Swap Store",
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"Select which store you'd like to swap this one with!"
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

		$sc = $this->stores[$response];
		if($sc->getId() == $store->getId()){
			$player->showModal(new ManageStoreUi($player, $cell, $store));
			return;
		}

		$sm->swapStores($store->getId(), $sc->getId());
		$player->showModal(new ManageStoresUi($player, $cell, "Successfully swapped store positions!", false));
	}

}