<?php namespace prison\trade\command;

use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;

use prison\{
	Prison,
	PrisonPlayer as Player
};
use prison\trade\request\TradeRequest;
use prison\trade\ui\TradeUi;

use core\utils\TextFormat;

class TradeCommand extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		if(!$sender instanceof Player){
			return;
		}
		if(count($args) === 0){
			$sender->showModal(new TradeUi($sender));
			return;
		}

		$ts = $sender->getGameSession()->getTrade();

		$name = array_shift($args);
		switch($name){
			case "open":
			case "o":
				if($ts->isTrading()){
					$ts->getTradeSession()->open($sender);
					return;
				}
				$sender->sendMessage(TextFormat::RI . "You don't have an active trade!");
				break;
			case "accept":
			case "yes":
			case "y":
				if($ts->isTrading()){
					$sender->sendMessage(TextFormat::RI . "You already have a trade open! Type " . TextFormat::YELLOW . "/trade open" . TextFormat::GRAY . " to access it!");
					return;
				}
				if(count($ts->getIncomingRequests()) == 0){
					$sender->sendMessage(TextFormat::RI . "You have no incoming trade requests.");
					return;
				}elseif(count($ts->getIncomingRequests()) > 1){
					if(count($args) == 0){
						$sender->sendMessage(TextFormat::RI . "You have multiple incoming trade requests. Type which one you'd like to accept with " . TextFormat::YELLOW . "/trade accept <name>");
						return;
					}
					$pl = strtolower(array_shift($args));
					foreach($ts->getIncomingRequests() as $req){
						if(strtolower($req->getFrom()) == $pl){
							$req->accept();
							return;
						}
					}
				}else{
					$request = array_values($ts->getIncomingRequests())[0];
					$request->accept();
				}
				break;
			case "deny":
			case "decline":
			case "no":
			case "n":
				if($ts->isTrading()){
					$sender->sendMessage(TextFormat::RI . "You already have a trade open! Type " . TextFormat::YELLOW . "/trade open" . TextFormat::GRAY . " to access it!");
					return;
				}
				if(count($ts->getIncomingRequests()) == 0){
					$sender->sendMessage(TextFormat::RI . "You have no incoming trade requests.");
					return;
				}elseif(count($ts->getIncomingRequests()) > 1){
					if(count($args) == 0){
						$sender->sendMessage(TextFormat::RI . "You have multiple incoming trade requests. Type which one you'd like to deny with " . TextFormat::YELLOW . "/trade deny <name>");
						return;
					}
					$pl = strtolower(array_shift($args));
					foreach($ts->getIncomingRequests() as $req){
						if(strtolower($req->getFrom()) == $pl){
							$req->decline(false);
							return;
						}
					}
				}else{
					$request = array_values($ts->getIncomingRequests())[0];
					$request->decline(false, "No");
				}
				break;

			case "cancel":
			case "nvm":
			case "c":
				if($ts->isTrading()){
					$ts->getTradeSession()->getInventory()->returnItems();
				}else{
					if(count($ts->getOutgoingRequests()) == 0){
						$sender->sendMessage(TextFormat::RI . "You have no outgoing trade requests or existing trades to cancel.");
						return;
					}elseif(count($ts->getOutgoingRequests()) > 1){
						if(count($args) == 0){
							$sender->sendMessage(TextFormat::RI . "You have multiple outgoing trade requests. Type which one you'd like to cancel with " . TextFormat::YELLOW . "/trade cancel <name>");
							return;
						}
						$pl = strtolower(array_shift($args));
						foreach($ts->getOutgoingRequests() as $req){
							if(strtolower($req->getTo()) == $pl){
								$req->cancel();
								$sender->sendMessage(TextFormat::GI . "Trade request has been cancelled.");
								return;
							}
						}
					}else{
						$request = array_values($ts->getOutgoingRequests())[0];
						$request->cancel();
						$sender->sendMessage(TextFormat::GI . "Trade request has been cancelled.");
					}
				}
				break;

			default:
				if($ts->isTrading()){
					$sender->sendMessage(TextFormat::RI . "You already have a trade open! Type " . TextFormat::YELLOW . "/trade open" . TextFormat::GRAY . " to access it!");
					return;
				}
				/** @var ?Player $plMatch */
				$plMatch = Server::getInstance()->getPlayerByPrefix($name);
				if($plMatch === null || !$plMatch->isLoaded()){
					$sender->sendMessage(TextFormat::RI . "You can only trade with players nearby you!");
					return;
				}
				if($plMatch === $sender){
					$sender->sendMessage(TextFormat::RI . "You cannot trade with yourself!");
					return;
				}
				$name = strtolower($plMatch->getName());

				foreach($ts->getIncomingRequests() as $req){
					if(strtolower($req->getFrom()) == $name){
						$sender->sendMessage(TextFormat::RI . "You already have an incoming trade request from this player. To accept it, type " . TextFormat::YELLOW . "/trade accept " . $plMatch->getName());
						return;
					}
				}

				foreach($ts->getOutgoingRequests() as $req){
					if(strtolower($req->getTo()) == $name){
						$sender->sendMessage(TextFormat::RI . "You already have an outgoing trade request to this player!");
						return;
					}
				}

				$request = new TradeRequest($sender, $plMatch);
				$sender->sendMessage(TextFormat::GI . "You've sent a trade request to " . TextFormat::YELLOW . $plMatch->getName());
				break;
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}