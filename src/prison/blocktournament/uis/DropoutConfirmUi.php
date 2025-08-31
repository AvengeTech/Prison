<?php namespace prison\blocktournament\uis;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class DropoutConfirmUi extends ModalWindow{

	public function __construct(){
		parent::__construct(
			"Confirm leave",
			"Are you sure you would like to leave this Block Tournament?",
			"Leave Tournament", "Go back"
		);
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$bt = Prison::getInstance()->getBlockTournament();
		$game = $bt->getGameManager()->getPlayerGame($player);
		if($game == null){
			$player->sendMessage(TextFormat::RI . "You are not in a Block Tournament!");
			return;
		}
		if($response){
			if(!$game->removePlayer($player)){
				$player->sendMessage(TextFormat::RI . "An error occured when trying to remove you from the game.");
				return;
			}
			$player->sendMessage(TextFormat::GI . "You have dropped out of the Block Tournament");
			return;
		}
		$player->showModal(new GameDetailsUi($game));
	}

}