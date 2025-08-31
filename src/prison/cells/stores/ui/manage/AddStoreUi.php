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

class AddStoreUi extends CustomForm{

	public $cell;

	public $items = [];

	public function __construct(
		Player $player, Cell $cell,
		string $name = "", string $description = "",
		string $message = "", bool $error = true
	) {
		/** @var PrisonPlayer $player */
		parent::__construct("Add Cell Store");
		$this->cell = $cell;

		$this->addElement(new Label(
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"Please fill out the information below to create a new Cell Store!"
		));
		$this->addElement(new Input("Store Name", "My Cell Store", $name));
		$this->addElement(new Input("Description", "Welcome to my shop!", $description));
		$items = $player->getInventory()->getContents();
		$names = [];
		foreach($items as $item){
			if(!$item instanceof Durable || $item->getDamage() == 0){
				$this->items[] = $item;
				$names[] = $item->getName() . TextFormat::RESET . TextFormat::WHITE . ($item->hasEnchantments() ? " (" . count($item->getEnchantments()) . " enchantments)" : "");
			}
		}
		$this->addElement(new Dropdown("First Stock", $names));
		$this->addElement(new Input("Stock Description (Optional)", "Special item, much wow"));
		$this->addElement(new Input("Price per", 1));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$cell = Prison::getInstance()->getCells()->getCellManager()->getCellByCell($this->cell);
		if(!$cell->isHolder($player)){
			$player->sendMessage(TextFormat::RI . "You no longer have access to this cell.");
			return;
		}
		$sm = $cell->getHolderBy($player)->getStoreManager();

		if(empty($this->items)){
			$player->showModal(new ManageStoresUi($player, $cell, "Your inventory is empty!"));
			return;
		}

		$name = $response[1];
		$description = $response[2];
		$item = $this->items[$response[3]];
		$sdescription = $response[4];
		$price = (int) $response[5];

		$item->setCount(1);

		if(strlen($name) < 2 || strlen($name) > 24){
			$player->showModal(new AddStoreUi($player, $cell, $name, $description, "Store name must be between 2 and 24 characters!"));
			return;
		}
		if(strlen($description) < 2 || strlen($description) > 155){
			$player->showModal(new AddStoreUi($player, $cell, $name, $description, "Store description must be between 2 and 155 characters!"));
			return;
		}

		if($item == null || !$player->getInventory()->contains($item)){
			$player->showModal(new AddStoreUi($player, $cell, $name, $description, "Your inventory does not contain this item anymore! Please select a new one and try again."));
			return;
		}
		if(strlen($sdescription) > 155){
			$player->showModal(new AddStoreUi($player, $cell, $name, $description, "Stock description must be under 155 characters!"));
			return;
		}

		if($price <= 0){
			$player->showModal(new AddStoreUi($player, $cell, $name, $description, "Price must be at least 1 techit!"));
			return;
		}

		$store = new Store($sm, $sm->getNewStoreId(), $player->getUser(), $name, $description);
		if(!$cell->isOwner($player) && $player->getRank() == "default") $store->setOpen(false);
		($stm = $store->getStockManager())->addStock(new Stock($stm, $stm->getNewStockId(), $item, 0, 0, $price, $sdescription));
		$player->showModal(new ConfirmAddStoreUi($player, $cell, $store));
	}

}