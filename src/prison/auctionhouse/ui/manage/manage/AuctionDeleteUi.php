<?php namespace prison\auctionhouse\ui\manage\manage;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\auctionhouse\ui\manage\AuctionManageUi;
use prison\auctionhouse\Auction;

use core\ui\windows\ModalWindow;

class AuctionDeleteUi extends ModalWindow{
	
	public function __construct(public Auction $auction){
		parent::__construct("Remove" . $auction->getName(),
			"Are you sure you would like to remove this item from the auction house?",
			"Remove auction",
			"Go back"
		);
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$am = Prison::getInstance()->getAuctionHouse()->getAuctionManager();
		$auction = $am->getAuctionByAuction($this->auction);
		if($auction === null){
			$player->showModal(new AuctionManageUi($player, "This auction is no longer on the auction house!"));
			return;
		}
		if($response){
			$inv = $auction->return();
			$am->removeAuction($auction);
			$player->showModal(new AuctionManageUi($player), "Auction has successfully been removed from the auction house and returned to your " . ($inv ? "inventory" : "inbox") . "!");
		}else{
			$player->showModal(new AuctionViewUi($auction));
		}
	}

}