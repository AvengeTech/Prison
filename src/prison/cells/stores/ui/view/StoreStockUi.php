<?php namespace prison\cells\stores\ui\view;

use pocketmine\player\Player;
use pocketmine\item\Durable;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\{
	Cell,
	CellHolder
};
use prison\cells\stores\{
	Store,
	Stock
};
use prison\techits\item\TechitNote;
use prison\mysteryboxes\items\KeyNote;

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Input
};
use core\utils\TextFormat;
use prison\enchantments\type\Enchantment;

class StoreStockUi extends CustomForm{

	public $cell;
	public $holder;
	public $store;
	public $stock;

	public function __construct(
		Player $player, Cell $cell, CellHolder $holder, Store $store, Stock $stock,
		string $message = "", bool $error = true
	) {
		/** @var PrisonPlayer $player */
		parent::__construct("Purchase Item");

		$this->cell = $cell;
		$this->holder = $holder;
		$this->store = $store;
		$this->stock = $stock;

		$item = $stock->getItem();
		$ench = $item->getEnchantments();
		$el = "";
		foreach($ench as $e){
			/** @var Enchantment $e */
			$ee = ($ens = Prison::getInstance()->getEnchantments())->getEnchantment($e->getId(), $e->getLevel());
			$el .= "- " . $ee->getName() . " " . $ens->getRoman($ee->getLevel()) . PHP_EOL;
		}

		$this->addElement(new Label(
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"Item: " . TextFormat::AQUA . "x" . $item->getCount() . " " . $item->getName() . TextFormat::WHITE . PHP_EOL .
			($item instanceof Durable ?
				"Used: " . ($item->getDamage() > 0 ? TextFormat::GREEN . "YES" : TextFormat::RED . "NO") . TextFormat::WHITE . PHP_EOL .
				($item->hasEnchantments() ?
					"Enchantments:" . PHP_EOL . $el . PHP_EOL
				: "")
			: (
				$item instanceof TechitNote ? "Techit value: " . $item->getTechits() . PHP_EOL : (
					$item instanceof KeyNote ?
						"Key value: x" . $item->getWorth() . " " . $item->getType() . PHP_EOL :
						""
					)
				)
			) .
			($stock->getDescription() != "" ? "Stock Description: " . $stock->getDescription() . TextFormat::RESET . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"Enter how many of this item you would like! There " . (($av = $stock->getAvailable()) == 1 ? "is" : "are") . " " . TextFormat::YELLOW . $av . TextFormat::WHITE . " left, selling at " . TextFormat::AQUA . number_format($stock->getFinalPrice()) . " techits " . TextFormat::WHITE . "each! " . ($stock->isSale() ? "(ON SALE: " . $stock->getFormattedSale() . ")" : "")
		));
		$this->addElement(new Input("Amount", 42, 1));
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
		$store = $sm->getStoreByStore($this->store);
		if($store === null){
			$player->showModal(new ViewStoresUi($player, $cell, $holder, "This store no longer exists!"));
			return;
		}
		if(!$store->isOpen()){
			$player->showModal(new ViewStoresUi($player, $cell, $holder, "This store is no longer open! Please select another one"));
			return;
		}
		$stm = $store->getStockManager();
		$stock = $stm->getStockByStock($this->stock);
		if($stock === null){
			$player->showModal(new ViewStoreUi($player, $cell, $holder, $store, "This stock no longer exists!"));
			return;
		}
		if($stock->getAvailable() <= 0){
			$player->showModal(new ViewStoreUi($player, $cell, $holder, $store, "This item is sold out!"));
			return;
		}

		$amount = (int) $response[1];
		if($amount <= 0){
			$player->showModal(new StoreStockUi($player, $cell, $holder, $store, $stock, "You must purchase at least one!"));
			return;
		}

		if($amount > $stock->getAvailable()){
			$player->showModal(new StoreStockUi($player, $cell, $holder, $store, $stock, "Please enter a smaller number! This store doesn't have that many items stocked."));
			return;
		}

		if($player->getTechits() < $stock->getFinalPrice($amount)){
			$player->showModal(new StoreStockUi($player, $cell, $holder, $store, $stock, "You cannot afford this many items!"));
			return;
		}

		$player->showModal(new PurchaseConfirmUi($player, $cell, $holder, $store, $stock, $amount));
	}

}