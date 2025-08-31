<?php namespace prison\auctionhouse\ui\select\auction;

use pocketmine\player\Player;

use core\ui\windows\ModalWindow;

use prison\auctionhouse\Auction;
use prison\auctionhouse\ui\select\AuctionSelectUi;
use prison\Prison;
use prison\PrisonPlayer;

use core\utils\TextFormat;

class BuyAuctionUi extends ModalWindow{
	
	public function __construct(public Auction $auction, public int $prevPage = 1, public array $auctions = []){
		parent::__construct("Buy now", "Are you sure you want to buy this auction now for " . TextFormat::AQUA . number_format($auction->getBuyNowPrice()) . " techits?", "Buy now", "Cancel");
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$am = Prison::getInstance()->getAuctionHouse()->getAuctionManager();
		$auction = $am->getAuctionByAuction($this->auction);
		if($auction === null){
			$player->showModal(new AuctionSelectUi($this->auctions, $this->prevPage, "This auction has expired! You were too late ;-;"));
			return;
		}
		if($response){
			if(!$auction->canBuyNow($player)){
				$player->showModal(new SingleAuctionUi($auction, $this->prevPage, $this->auctions, "You do not have enough techits to purchase this auction!"));
				return;
			}
			if(!$player->getInventory()->canAddItem($auction->getItem())){
				$player->showModal(new SingleAuctionUi($auction, $this->prevPage, $this->auctions, "You do not have enough inventory space to purchase this!"));
				return;
			}
			$auction->buyNow($player);
			$am->removeAuction($auction);
			$player->showModal(new AuctionSelectUi($this->auctions, $this->prevPage, "You received x" . $this->auction->getItem()->getCount() . " " . $auction->getItem()->getName() . " for " . TextFormat::AQUA . number_format($auction->getBuyNowPrice()) . " techits!", false));
			return;
		}
		$player->showModal(new SingleAuctionUi($auction, $this->prevPage));
	}
}