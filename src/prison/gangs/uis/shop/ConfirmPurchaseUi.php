<?php namespace prison\gangs\uis\shop;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\{
	Gang,
	GangMember
};
use prison\gangs\shop\{
	LevelShop,
	ShopStock
};

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class ConfirmPurchaseUi extends ModalWindow{

	public $shop;
	public $stock;

	public function __construct(Player $player, Gang $gang, LevelShop $shop, ShopStock $stock){
		$this->shop = $shop;
		$this->stock = $stock;
		
		parent::__construct("Confirm Purchase", "Are you sure you would like to purchase x" . $stock->getItem()->getCount() . " " . $stock->getName() . " for " . TextFormat::GOLD . $stock->getPrice() . " trophies?", "Purchase", "Go back");
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gm = Prison::getInstance()->getGangs()->getGangManager();
		if(!$gm->inGang($player)){
			$player->sendMessage(TextFormat::RI . "You are not in a gang!");
			return;
		}
		$gang = $gm->getPlayerGang($player);
		$member = $gang->getMemberManager()->getMember($player);
		$role = $member->getRole();
		if($role < GangMember::ROLE_ELDER){
			$player->sendMessage(TextFormat::RI . "You must be at least a gang elder to use this subcommand!");
			return;
		}

		$shop = $this->shop;

		if($response){
			$stock = $this->stock;
			if($gang->getTrophies() < $stock->getPrice()){
				$player->showModal(new LevelShopUi($player, $gang, $shop, "Your gang does not have enough trophies to purchase this!"));
				return;
			}
			$stock->buy($player, $gang);
			$player->showModal(new LevelShopUi($player, $gang, $shop, "Successfully purchased x" . $stock->getItem()->getCount() . " " . $stock->getName() . "!", false));
			return;
		}
		$player->showModal(new LevelShopUi($player, $gang, $shop));
	}

}