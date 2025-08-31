<?php namespace prison\cells\stores\ui\manage;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\Cell;
use prison\cells\stores\{
	Store,
	Stock
};

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Input
};
use core\utils\TextFormat;

class EditStockDescriptionUi extends CustomForm{

	public $cell;
	public $store;
	public $stock;

	public function __construct(
		Player $player, Cell $cell, Store $store, Stock $stock,
		string $description = "",
		string $message = "", bool $error = true
	) {
		/** @var PrisonPlayer $player */
		parent::__construct("Edit Stock Description");
		$this->cell = $cell;
		$this->store = $store;
		$this->stock = $stock;

		$this->addElement(new Label(
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"Type your new stock description in the box below"
		));
		$this->addElement(new Input("Stock Description", "My Cell Stock", ($description == "" ? $stock->getDescription() : $description)));
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
		$stock = $stm->getStockByStock($this->stock);
		if($stock === null){
			$player->showModal(new ManageStockUi($player, $cell, $store, "This stock no longer exists!"));
			return;
		}

		$description = $response[1];

		if(strlen($description) > 155){
			$player->showModal(new EditStockDescriptionUi($player, $cell, $store, $stock, $description, "Store description must be less than 155 characters!"));
			return;
		}

		$stock->setDescription($description);

		$player->showModal(new EditStockUi($player, $cell, $store, $stock, "Successfully updated your stock's description!", false));
	}

}