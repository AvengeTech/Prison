<?php namespace prison\cells\stores\ui\manage;

use pocketmine\player\Player;
use pocketmine\item\Durable;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\{
	Cell,
};
use prison\cells\stores\{
	Store,
	Stock
};

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Input,
	Dropdown
};
use core\utils\TextFormat;

class AddStockUi extends CustomForm{

	public $cell;
	public $store;

	public $items = [];

	public function __construct(
		Player $player, Cell $cell, Store $store,
		string $description = "",
		string $message = "", bool $error = true
	){
		/** @var PrisonPlayer $player */
		parent::__construct("Add New Stock");
		$this->cell = $cell;
		$this->store = $store;

		$this->addElement(new Label(
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"Please fill out the information below to add new stock to your store!"
		));
		$items = $player->getInventory()->getContents();
		$names = [];
		foreach($items as $item){
			if(!$item instanceof Durable || $item->getDamage() == 0){
				$this->items[] = $item;
				$names[] = $item->getName() . TextFormat::RESET . TextFormat::WHITE . ($item->hasEnchantments() ? " (" . count($item->getEnchantments()) . " enchantments)" : "");
			}
		}
		$this->addElement(new Dropdown("Stock Item", $names));
		$this->addElement(new Input("Stock Description (Optional)", "Special item, much wow"));
		$this->addElement(new Input("Price per", 1));
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

		if(empty($this->items)){
			$player->showModal(new ManageStockUi($player, $cell, $store, "Your inventory is empty!"));
			return;
		}

		$item = $this->items[$response[1]];
		$description = $response[2];
		$price = (int) $response[3];

		$item->setCount(1);

		if($item == null || !$player->getInventory()->contains($item)){
			$player->showModal(new AddStockUi($player, $cell, $store, $description, "Your inventory does not contain this item anymore! Please select a new one and try again."));
			return;
		}
		if(strlen($description) > 155){
			$player->showModal(new AddStockUi($player, $cell, $store, $description, "Stock description must be under 155 characters!"));
			return;
		}

		if($price <= 0){
			$player->showModal(new AddStockUi($player, $cell, $store, $description, "Price must be at least 1 techit!"));
			return;
		}

		$stm = $store->getStockManager();
		$stock = new Stock($stm, $stm->getNewStockId(), $item, 0, 0, $price, $description);
		$player->showModal(new ConfirmAddStockUi($player, $cell, $store, $stock));
	}

}