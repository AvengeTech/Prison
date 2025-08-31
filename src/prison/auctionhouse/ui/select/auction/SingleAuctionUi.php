<?php namespace prison\auctionhouse\ui\select\auction;

use pocketmine\player\Player;
use pocketmine\item\Durable;

use prison\Prison;
use prison\PrisonPlayer;
use prison\auctionhouse\ui\select\AuctionSelectUi;
use prison\auctionhouse\Auction;
use prison\techits\item\TechitNote;
use prison\mysteryboxes\items\KeyNote;

use core\utils\TextFormat;
use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

class SingleAuctionUi extends SimpleForm{
	
	public function __construct(public Auction $auction, public int $prevPage = 1, public array $auctions = [], string $message = "", bool $error = true){

		$item = $auction->getItem();

		$ench = $item->getEnchantments();
		$el = "";
		foreach($ench as $e){
			$ee = ($ens = Prison::getInstance()->getEnchantments())->getEWE($e);
			$el .= "- " . $ee->getName() . " " . $ens->getRoman($e->getLevel()) . PHP_EOL;
		}

		parent::__construct($auction->getName(),
			($message !== "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"Auction name: " . $auction->getName() . PHP_EOL .
			"Owned by: " . TextFormat::YELLOW . $auction->getOwner()->getGamertag() . TextFormat::WHITE . PHP_EOL .
			"Item: " . TextFormat::AQUA . "x" . $item->getCount() . " " . $item->getName() . ($item->hasCustomName() ? " (" . $item->getVanillaName() . ")" : "") . TextFormat::RESET . TextFormat::WHITE . PHP_EOL .
			($item instanceof Durable ?
				"Used: " . ($item->getDamage() > 0 ? TextFormat::GREEN . "YES" : TextFormat::RED . "NO") . TextFormat::WHITE . PHP_EOL .
				($item->hasEnchantments() ?
					"Enchantments:" . PHP_EOL . $el . PHP_EOL
				: "")
			: (
				$item instanceof TechitNote ? "Techit value: " . $item->getTechits() . PHP_EOL : (
					$item instanceof KeyNote ?
						"Key value: x" . $item->getWorth() . " " . $item->getType() . PHP_EOL :
						""
					)
				)
			) .
			($auction->getBidder() !== null ?
				"Highest bid: " . TextFormat::AQUA . number_format($auction->getBid()) . " techits" . TextFormat::WHITE . PHP_EOL .
				"Bidder: " . TextFormat::YELLOW . $auction->getBidder()->getGamertag()
			:
				"Starting bid: " . TextFormat::AQUA . number_format($auction->getStartingBid()) . " techits" . TextFormat::WHITE . PHP_EOL .
				TextFormat::RED . "No bids have been placed yet!"
			) . TextFormat::WHITE . PHP_EOL . PHP_EOL .

			"Buy now price: " . TextFormat::AQUA . number_format($auction->getBuyNowPrice())
		);

		$this->addButton(new Button("Bid"));
		$this->addButton(new Button("Purchase now"));
		$this->addButton(new Button("Go back"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$am = Prison::getInstance()->getAuctionHouse()->getAuctionManager();
		$auction = $am->getAuctionByAuction($this->auction);
		if($response == 0 || $response == 1){
			if($auction === null){
				$player->showModal(new AuctionSelectUi([], $this->prevPage, "This auction has expired! If you are the last bidder of this auction, it should appear in your Auction Bin."));
				return;
			}
		}
		if($response == 0){
			$player->showModal(new BidAuctionUi($auction, $this->prevPage, $this->auctions));
			return;
		}
		if($response == 1){
			$player->showModal(new BuyAuctionUi($auction, $this->prevPage, $this->auctions));
			return;
		}
		$player->showModal(new AuctionSelectUi($this->auctions, $this->prevPage));
	}

}