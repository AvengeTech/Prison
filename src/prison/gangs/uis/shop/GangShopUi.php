<?php namespace prison\gangs\uis\shop;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\Gang;

use core\ui\windows\SimpleForm;
use core\utils\TextFormat;

class GangShopUi extends SimpleForm{

	public $shops = [];

	public function __construct(Player $player, Gang $gang, string $error = ""){
		parent::__construct(
			"Gang Shop",
			($error == "" ? "" : TextFormat::RED . "Error: " . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL) .
			"Tap a shop below to view it's contents! Your gang has " . TextFormat::GOLD . $gang->getTrophies() . " trophies"
		);

		$shop = Prison::getInstance()->getGangs()->getGangManager()->getGangShop();
		foreach($shop->getShops() as $shop){
			$this->shops[] = $shop;
			$this->addButton($shop->getButton($gang));
		}
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gm = Prison::getInstance()->getGangs()->getGangManager();
		if(!$gm->inGang($player)){
			$player->sendMessage(TextFormat::RI . "You are no longer in a gang!");
			return;
		}
		$gang = $gm->getPlayerGang($player);

		$shop = $this->shops[$response];
		if($gang->getLevel() < $shop->getLevel()){
			$player->showModal(new GangShopUi($player, $gang, "Your gang is not a high enough level to access this shop!"));
			return;
		}

		$player->showModal(new LevelShopUi($player, $gang, $shop));
	}

}