<?php namespace prison\trade\ui;

use pocketmine\player\Player;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use core\utils\TextFormat;
use prison\PrisonPlayer;

class TradeUi extends SimpleForm{

	public $session;

	public function __construct(Player $player){
		/** @var PrisonPlayer $player */
		parent::__construct("Trading", "Select an option below.");

		$session = $this->session = $player->getGameSession()->getTrade();

		$this->addButton(new Button("Manage Incoming Requests (" . count($session->incoming) . ")"));
		$this->addButton(new Button("Manage Outgoing Requests (" . count($session->outgoing) . ")"));

		if($session->isTrading()){
			$this->addButton(new Button("Open ongoing trade"));
			$this->addButton(new Button("Cancel ongoing trade"));
		}
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$session = $this->session;

		if($response == 0){
			if($session->isTrading()){
				$player->sendMessage(TextFormat::RI . "You cannot open this menu while trading!");
				return;
			}
			$player->showModal(new IncomingRequestsUi($session));
			return;
		}
		if($response == 1){
			if($session->isTrading()){
				$player->sendMessage(TextFormat::RI . "You cannot open this menu while trading!");
				return;
			}
			$player->showModal(new OutgoingRequestsUi($session));
			return;
		}
		if($response == 2){
			if(!$session->isTrading()){
				$player->sendMessage(TextFormat::RI . "Your trade session is no longer valid.");
				return;
			}
			$session->getTradeSession()->open($player);
			return;
		}
		if($response == 3){
			if(!$session->isTrading()){
				$player->sendMessage(TextFormat::RI . "Your trade session is no longer valid.");
				return;
			}
			$session->getTradeSession()->getInventory()->returnItems();
		}
	}

}