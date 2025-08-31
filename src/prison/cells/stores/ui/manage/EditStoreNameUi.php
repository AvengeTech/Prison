<?php namespace prison\cells\stores\ui\manage;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\Cell;
use prison\cells\stores\Store;

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Input
};
use core\utils\TextFormat;

class EditStoreNameUi extends CustomForm{

	public $cell;
	public $store;

	public function __construct(
		Player $player, Cell $cell, Store $store,
		string $name = "",
		string $message = "", bool $error = true
	) {
		/** @var PrisonPlayer $player */
		parent::__construct("Edit Store Name");
		$this->cell = $cell;
		$this->store = $store;

		$this->addElement(new Label(
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"Type your new store name in the box below"
		));
		$this->addElement(new Input("Store Name", "My Cell Store", ($name == "" ? $store->getName() : $name)));
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

		$name = $response[1];

		if(strlen($name) < 2 || strlen($name) > 24){
			$player->showModal(new EditStoreNameUi($player, $cell, $store, $name, "Store name must be between 2 and 24 characters!"));
			return;
		}

		$store->setName($name);

		$player->showModal(new ManageStoreUi($player, $cell, $store, "Successfully renamed your store!", false));
	}

}