<?php namespace prison\auctionhouse\ui\manage;

use pocketmine\player\Player;

use prison\auctionhouse\ui\{
	MainAuctionUi,
	manage\manage\AuctionViewUi
};
use prison\Prison;
use prison\PrisonPlayer;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class AuctionManageUi extends SimpleForm{

	public $auctions = [];

	public function __construct(Player $player, string $message = "", bool $error = true){
		parent::__construct("Auction Manager",
			($message ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"View your Auctions..."
		);

		$this->addButton(new Button("Create new auction"));

		$this->auctions = Prison::getInstance()->getAuctionHouse()->getAuctionManager()->getPlayerAuctions($player);
		foreach($this->auctions as $auction){
			$this->addButton($auction->getButton());
		}

		$this->addButton(new Button("Go back"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		if($response == 0){
			$player->showModal(new ChooseAuctionItemUi($player));
			return;
		}
		$am = Prison::getInstance()->getAuctionHouse()->getAuctionManager();
		foreach($this->auctions as $key => $auction){
			if($response - 1 == $key){
				if(($auction = $am->getAuctionByAuction($auction)) == null){
					$player->showModal(new AuctionManageUi($player, "This auction has expired!"));
					return;
				}
				$player->showModal(new AuctionViewUi($auction));
				return;
			}
		}
		if($response == count($this->auctions) + 1){
			$player->showModal(new MainAuctionUi());
		}
	}

}