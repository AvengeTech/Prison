<?php namespace prison\auctionhouse\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;
use prison\auctionhouse\ui\{
	MainAuctionUi,
	select\AuctionSelectUi
};

use core\Core;
use core\utils\TextFormat;

class AuctionHouseCommand extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["ah"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		/** @var PrisonPlayer $sender */
		if(Core::thisServer()->isSubServer()){
			$sender->sendMessage(TextFormat::RI . "Auction house can only be used at spawn!");
			return;
		}
		if(count($args) == 0){
			$sender->showModal(new MainAuctionUi());
			return;
		}

		$auctions = Prison::getInstance()->getAuctionHouse()->getAuctionManager()->getPlayerAuctions($args[0]);
		if(count($auctions) == 0){
			$sender->sendMessage(TextFormat::RI . "This player has no auctions available!");
			return;
		}
		$sender->showModal(new AuctionSelectUi($auctions));
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}