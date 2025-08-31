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

class EditStoreDescriptionUi extends CustomForm{

	public $cell;
	public $store;

	public function __construct(
		Player $player, Cell $cell, Store $store,
		string $description = "",
		string $message = "", bool $error = true
	) {
		/** @var PrisonPlayer $player */
		parent::__construct("Edit Store Description");
		$this->cell = $cell;
		$this->store = $store;

		$this->addElement(new Label(
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"Type your new store description in the box below"
		));
		$this->addElement(new Input("Store Description", "My Cell Store", ($description == "" ? $store->getDescription() : $description)));
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

		$description = $response[1];

		if(strlen($description) > 155){
			$player->showModal(new EditStoreDescriptionUi($player, $cell, $store, $description, "Store description must be less than 155 characters!"));
			return;
		}

		$store->setDescription($description);

		$player->showModal(new ManageStoreUi($player, $cell, $store, "Successfully updated your store's description!", false));
	}

}