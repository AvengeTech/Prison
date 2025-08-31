<?php namespace prison\quests\shop\uis;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

use prison\Prison;
use prison\PrisonPlayer;

use core\ui\elements\simpleForm\Button;
use core\ui\windows\SimpleForm;

class CategoryUi extends SimpleForm{

	public $id;
	public $items = [];

	public function __construct($id, Player $player) {
		/** @var PrisonPlayer $player */
		$this->id = $id;

		$category = Prison::getInstance()->getQuests()->getShop()->getCategory($id);
		$items = $category->getItems();
		foreach($items as $item){
			$this->items[] = $item;
			$this->addButton($item->getButton());
		}
		$this->addButton(new Button("Go back"));

		$session = $player->getGameSession()->getQuests();
		parent::__construct($category->getName(), "You have " . $session->getPoints() . " quest points to spend!" . PHP_EOL . PHP_EOL . "What would you like to do?");
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		foreach($this->items as $key => $item){
			if($key == $response){
				$session = $player->getGameSession()->getQuests();
				if($session->getPoints() < $item->getPrice()){
					$player->showModal(new MainShopUi($player, TextFormat::RED . "You don't have enough Quest Points for this item!"));
					return;
				}
				$player->showModal(new ConfirmPurchaseUi($this->id, $item));
				return;
			}
		}
		$player->showModal(new MainShopUi($player));
	}

}