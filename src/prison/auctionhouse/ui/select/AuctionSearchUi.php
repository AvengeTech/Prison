<?php namespace prison\auctionhouse\ui\select;

use pocketmine\player\Player;

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	StepSlider,
	Input,
	Toggle
};

use prison\Prison;
use prison\PrisonPlayer;

class AuctionSearchUi extends CustomForm{

	public function __construct(){
		parent::__construct("Search Auction House");

		$this->addElement(new Label("Use this menu to search for specific Auctions available"));
		$this->addElement(new Label("Simple"));

		$this->addElement(new Input("Auction Name", "auction"));
		$this->addElement(new Input("Created By", "Gamertag..."));
		$this->addElement(new Input("Item", "Item Name or Exact ID"));

		$this->addElement(new Toggle("Enchanted", false));

		$this->addElement(new Label("Advanced"));

		$this->addElement(new StepSlider("Sort by", ["None", "Bid - Highest to Lowest", "Bid - Lowest to Highest", "Buy - Highest to Lowest", "Buy - Lowest to Highest", "Latest", "Oldest"]));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$name = $response[2];
		$by = $response[3];
		$item = $response[4];
		$enchanted = $response[5];
		$sortby = $response[7];

		$criteria = [
			"name" => $name,
			"by" => $by,
			"item" => $item,
			"enchanted" => $enchanted,
			"sortby" => $sortby,
		];

		$auctions = Prison::getInstance()->getAuctionHouse()->getAuctionManager()->getAuctionsBySearch($criteria);
		if(empty($auctions)){
			$player->showModal(new AuctionSelectUi([], 1, "No auctions matched your search!"));
			return;
		}
		$player->showModal(new AuctionSelectUi($auctions, 1, "Found " . count($auctions) . " auctions that match your search!", false));
	}

}