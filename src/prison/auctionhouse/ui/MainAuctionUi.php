<?php namespace prison\auctionhouse\ui;

use pocketmine\player\Player;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use prison\auctionhouse\ui\{
	manage\AuctionManageUi,
	select\AuctionSelectUi
};
use prison\Prison;
use prison\PrisonPlayer;

use core\utils\TextFormat;

class MainAuctionUi extends SimpleForm{

	public function __construct(string $message = "", bool $error = true){
		parent::__construct("Auction House",
			($message ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"Welcome to the Auction House. Here, you can auction off unwanted items in return for Techits!" . PHP_EOL . PHP_EOL . 
			"Choose an option below to get started."
		);

		$this->addButton(new Button("View ongoing Auctions" . PHP_EOL . "(" . count(Prison::getInstance()->getAuctionHouse()->getAuctionManager()->getAuctions()) . " total running)"));
		$this->addButton(new Button("Auction Manager"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		if($response == 0){
			if(count(Prison::getInstance()->getAuctionHouse()->getAuctionManager()->getAuctions()) <= 0){
				$player->showModal(new MainAuctionUi("There are no auctions available!"));
				return;
			}
			$player->showModal(new AuctionSelectUi());
			return;
		}
		if($response == 1){
			$player->showModal(new AuctionManageUi($player));
		}
	}

}