<?php namespace prison\trade\ui;

use pocketmine\player\Player;

use prison\trade\request\TradeRequest;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;
use prison\PrisonPlayer;

class ManageIncomingRequestUi extends SimpleForm{
	
	public function __construct(public TradeRequest $request){
		parent::__construct("Manage Request", "Choose an option.");
		
		$this->addButton(new Button("Accept"));
		$this->addButton(new Button("Decline"));
		$this->addButton(new Button("Go back"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$request = $this->request;

		if($request->closed){
			$player->sendMessage(TextFormat::RI . "This request no longer exists.");
			return;
		}

		if($response == 0){
			$request->accept();
			return;
		}
		if($response == 1){
			$request->decline(false, "Request declined.");
			return;
		}
		if($response == 2){
			$player->showModal(new TradeUi($player));
		}
			
	}

}