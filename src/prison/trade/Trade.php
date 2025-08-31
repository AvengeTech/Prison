<?php namespace prison\trade;

use pocketmine\Server;
use pocketmine\player\Player;

use prison\{
	Prison,
	PrisonPlayer
};
use prison\trade\command\TradeCommand;

class Trade{

	public static int $requestCount = 0;

	public function __construct(public Prison $plugin){
		$plugin->getServer()->getCommandMap()->register("trade", new TradeCommand($plugin, "trade", "Open the trade menu"));
	}

	public function close() : void{
			/** @var PrisonPlayer $player */
		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			if($player->hasGameSession() && ($ts = $player->getGameSession()->getTrade())->isTrading()){
				$tradesession = $ts->getTradeSession();
				$tradesession->getInventory()->returnItems();
			}
		}
	}

	public function onQuit(Player $player) : void{
		/** @var PrisonPlayer $player */
		if(!$player->hasGameSession()) return;
		$session = $player->getGameSession()->getTrade();
		if($session->isTrading()){
			$trade = $session->getTradeSession();
			$trade->getInventory()->returnItems();
		}
		foreach($session->getIncomingRequests() as $request){
			$request->decline(false, "Player has left the server");
		}
		foreach($session->getOutgoingRequests() as $request){
			$request->cancel();
		}
	}

}