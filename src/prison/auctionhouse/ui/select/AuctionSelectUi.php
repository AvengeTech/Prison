<?php namespace prison\auctionhouse\ui\select;

use pocketmine\player\Player;

use prison\auctionhouse\ui\{
	MainAuctionUi,

	select\auction\SingleAuctionUi,
	manage\manage\AuctionViewUi
};
use prison\Prison;
use prison\PrisonPlayer;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class AuctionSelectUi extends SimpleForm{

	const PAGE_SIZE = 10;

	public $auctions = [];
	public $page;

	public $auctionpage = [];

	public $hasBack = false;
	public $hasNext = false;

	public function __construct(array $auctions = [], int $page = 1, string $message = "", bool $error = true){
		$this->addButton(new Button("Search ..."));

		$am = Prison::getInstance()->getAuctionHouse()->getAuctionManager();
		if(empty($auctions)){
			$this->auctions = $am->getAuctions();
		}else{
			$this->auctions = $am->validateAuctions($auctions);
		}
		$this->page = $page;

		parent::__construct("Auction House (" . $page . "/" . $am->getTotalPages($this->auctions) . ")", ($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . PHP_EOL . PHP_EOL . TextFormat::WHITE : "") . "Tap an auction to view more details!");

		$this->auctionpage = $am->getPage($this->auctions, $page);

		foreach($this->auctionpage as $auction){
			$this->addButton($auction->getButton());
		}

		if($am->hasBackPage($this->auctions, $page)){
			$this->hasBack = true;
			$this->addButton(new Button("Previous Page (" . ($page - 1) . "/" . $am->getTotalPages($this->auctions) . ")"));
		}
		if($am->hasNextPage($this->auctions, $page)){
			$this->hasNext = true;
			$this->addButton(new Button("Next Page (" . ($page + 1) . "/" . $am->getTotalPages($this->auctions) . ")"));
		}
		$this->addButton(new Button("Go back"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		if($response == 0){
			$player->showModal(new AuctionSearchUi());
			return;
		}
		foreach($this->auctionpage as $key => $auction){
			if($response - 1 == $key){
				$am = Prison::getInstance()->getAuctionHouse()->getAuctionManager();
				if(($auction = $am->getAuctionByAuction($auction)) == null){
					$player->showModal(new AuctionSelectUi($this->auctions, $this->page, "This auction has expired! If you are the last bidder of this auction, it should appear in your Auction Bin."));
					return;
				}
				if($auction->getOwner()->getXuid() == $player->getXuid()){
					$player->showModal(new AuctionViewUi($auction));
					return;
				}
				$player->showModal(new SingleAuctionUi($auction, $this->page, $this->auctions));
				return;
			}
		}
		if($this->hasBack && $this->hasNext){
			if($response == count($this->auctionpage) + 1){
				$player->showModal(new AuctionSelectUi($this->auctions, $this->page - 1));
				return;
			}
			if($response == count($this->auctionpage) + 2){
				$player->showModal(new AuctionSelectUi($this->auctions, $this->page + 1));
				return;
			}
		}
		if($this->hasBack){
			if($response == count($this->auctionpage) + 1){
				$player->showModal(new AuctionSelectUi($this->auctions, $this->page - 1));
				return;
			}
		}
		if($this->hasNext){
			if($response == count($this->auctionpage) + 1){
				$player->showModal(new AuctionSelectUi($this->auctions, $this->page + 1));
				return;
			}
		}
		$player->showModal(new MainAuctionUi());
	}

}