<?php namespace prison\auctionhouse\ui\select\auction;

use pocketmine\player\Player;

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{Label,Input};

use prison\auctionhouse\Auction;
use prison\auctionhouse\ui\select\AuctionSelectUi;
use prison\Prison;
use prison\PrisonPlayer;

use core\utils\TextFormat;

class BidAuctionUi extends CustomForm{
	
	public function __construct(public Auction $auction, public int $prevPage = 1, public array $auctions = [], string $message = "", bool $error = true){
		parent::__construct("Bidding...");

		$this->addElement(new Label(
			($message !== "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"You are bidding on this auction. You must type a value higher than " . TextFormat::AQUA . number_format(($auction->getBid() == 0 ? $auction->getStartingBid() : $auction->getBid())) . " techits" . TextFormat::WHITE . " (Current bid value)"
		));
		$this->addElement(new Input("Bid Value", "Techit value"));
		$this->addElement(new Label("By pressing submit, you will bid on this auction. This will take however many techits from your balance and put you as the highest bidder."));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$am = Prison::getInstance()->getAuctionHouse()->getAuctionManager();
		$auction = $am->getAuctionByAuction($this->auction);

		if($auction == null){
			$player->showModal(new AuctionSelectUi($this->auctions, $this->prevPage, "This auction has expired! If you are the last bidder of this auction, it should appear in your Auction Bin."));
			return;
		}

		$value = (int) $response[1];
		$bid = ($auction->getBid() == 0 ? $auction->getStartingBid() : $auction->getBid());

		if($value <= $bid){
			$player->showModal(new BidAuctionUi($auction, $this->prevPage, $this->auctions, "Value entered is lower than current auction bid or the bid has recently increased!"));
			return;
		}
		if($player->getTechits() < $value){
			$player->showModal(new SingleAuctionUi($auction, $this->prevPage, $this->auctions, "You do not have enough techits to bid!"));
			return;
		}

		$auction->setNewBidder($player, $value);
		$player->showModal(new SingleAuctionUi($auction, $this->prevPage, $this->auctions, "You bidded " . TextFormat::AQUA . number_format($value) . " techits" . TextFormat::GREEN . " on this item!", false));
	}

}