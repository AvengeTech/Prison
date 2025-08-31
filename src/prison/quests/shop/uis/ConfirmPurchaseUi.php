<?php namespace prison\quests\shop\uis;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

use prison\PrisonPlayer;
use prison\quests\shop\ShopItem;

use core\ui\windows\ModalWindow;

class ConfirmPurchaseUi extends ModalWindow{

	public function __construct(public int $id, public ShopItem $item){
		parent::__construct("Confirm Purchase", "You are spending " . $item->getPrice() . " Quest Points on:" . PHP_EOL . " - " . $item->getName() . PHP_EOL . PHP_EOL . "Are you sure you want to purchase this item?", "Purchase", "Go back");
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if($response){
			$item = $this->item;
			if (!$item->give($player)) {
				$player->showModal(new MainShopUi($player, TextFormat::RED . "Failed to purchase " . $item->getName() . "! Please report this error!"));
				return;
			}
			$session = $player->getGameSession()->getQuests();
			$session->takePoints($item->getPrice());

			$player->showModal(new MainShopUi($player, TextFormat::GREEN . "Purchased " . $item->getName()));
		}else{
			$player->showModal(new CategoryUi($this->id, $player));
		}
	}

}