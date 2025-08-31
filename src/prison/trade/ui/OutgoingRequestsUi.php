<?php namespace prison\trade\ui;

use pocketmine\player\Player;

use prison\trade\TradeComponent;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;
use prison\PrisonPlayer;

class OutgoingRequestsUi extends SimpleForm{

	public array $requests;

	public function __construct(public TradeComponent $session){
		parent::__construct("Outgoing Requests", "Tap one to cancel it.");
		
		$this->addButton(new Button("Send Trade Request"));
		foreach(($requests = array_values($session->getOutgoingRequests())) as $request){
			$this->addButton(new Button("To: " . $request->getTo()->getName() . "\n" . "Tap to cancel"));
		}
		$this->addButton(new Button("Go back"));

		$this->requests = $requests;
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$session = $this->session;
		if($response == 0){
			$player->showModal(new SendRequestUi($player));
			return;
		}
		foreach($this->requests as $key => $request){
			if($key == $response - 1){
				if($request->closed){
					$player->sendMessage(TextFormat::RI . "This request has expired.");
					return;
				}
				$request->cancel();
				break;
			}
		}

		$player->showModal(new TradeUi($player));
	}

}