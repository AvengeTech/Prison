<?php namespace prison\gangs\uis\shop;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\Gang;
use prison\gangs\shop\LevelShop;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class LevelShopUi extends SimpleForm{

	public $shop;
	public $stock = [];

	public function __construct(Player $player, Gang $gang, LevelShop $shop, string $message = "", bool $error = true){
		$this->shop = $shop;

		parent::__construct(
			"Gang Shop (Level " . $shop->getLevel() . ")",
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"Tap an item below to purchase! Your gang has " . TextFormat::GOLD . $gang->getTrophies() . " trophies"
		);

		foreach($shop->getStock() as $stock){
			$this->stock[] = $stock;
			$this->addButton($stock->getButton());
		}
		$this->addButton(new Button("Go back"));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gm = Prison::getInstance()->getGangs()->getGangManager();
		if(!$gm->inGang($player)){
			$player->sendMessage(TextFormat::RI . "You are no longer in a gang!");
			return;
		}
		$gang = $gm->getPlayerGang($player);

		$stock = $this->stock[$response] ?? null;
		if($stock == null){
			$player->showModal(new GangShopUi($player, $gang));
			return;
		}
		if($gang->getTrophies() < $stock->getPrice()){
			$player->showModal(new LevelShopUi($player, $gang, $this->shop, "Your gang doesn't have enough trophies to afford this!"));
			return;
		}

		$player->showModal(new ConfirmPurchaseUi($player, $gang, $this->shop, $stock));
	}

}