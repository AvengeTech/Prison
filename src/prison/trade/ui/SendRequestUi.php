<?php namespace prison\trade\ui;

use pocketmine\{
	player\Player,
	Server
};

use prison\trade\request\TradeRequest;

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Dropdown
};

use core\utils\TextFormat;
use prison\PrisonPlayer;

class SendRequestUi extends CustomForm{

	public array $players = [];

	public function __construct(Player $pl, string $error = ""){
		parent::__construct("Send Trade Request");

		$this->addElement(new Label("Select a nearby player you would like to trade with." . ($error == "" ? "" : "\n" . TextFormat::RED . "Error: " . $error)));
		$players = [];
		/** @var PrisonPlayer #player */
		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			if($player->getName() !== $pl->getName() && $player->isLoaded()) $players[] = $player->getName();
		}
		$this->players = $players;
		$this->addElement(new Dropdown("Players", $players));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		if(count($this->players) == 0) return;
		$name = $this->players[$response[1]];

		/** @var PrisonPlayer $to */
		$to = Server::getInstance()->getPlayerByPrefix($name);
		if(!$to instanceof Player || !$to->isLoaded()){
			$player->showModal(new SendRequestUi($player, "Player no longer nearby!"));
			return;
		}
		if($to === $player){
			$player->showModal(new SendRequestUi($player, "You cannot send a trade request to yourself!"));
			return;
		}
		$tosession = $to->getGameSession()->getTrade();
		foreach($tosession->getIncomingRequests() as $request){
			if($request->getFrom() == $player){
				$player->showModal(new SendRequestUi($player, "You've already sent this player a trade request!"));
				return;
			}
		}
		foreach($tosession->getOutgoingRequests() as $request){
			if($request->getTo() == $player){
				$player->showModal(new SendRequestUi($player, "This player has already sent you a trade request!"));
				return;
			}
		}

		$request = new TradeRequest($player, $to);
		$player->sendMessage(TextFormat::GI . "You've sent a trade request to " . TextFormat::YELLOW . $to->getName());
	}

}