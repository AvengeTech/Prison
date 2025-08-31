<?php namespace prison\blocktournament\uis;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\blocktournament\Game;

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class CancelGameUi extends ModalWindow{

	public $game;

	public function __construct(Game $game){
		$this->game = $game;
		parent::__construct(
			"Confirm Cancel",
			"Are you sure you would like to cancel this Block Tournament? You cannot undo this action.",
			"Cancel Tournament", "Go back"
		);
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$bt = Prison::getInstance()->getBlockTournament();
		$game = $bt->getGameManager()->getGameFrom($this->game);
		if($game == null){
			$player->sendMessage(TextFormat::RI . "Block Tournament doesn't exist!");
			return;
		}
		if(!$game->canEdit($player)){
			$player->sendMessage(TextFormat::RI . "You do not have permission to edit this tournament!");
			return;
		}
		if(!$game->isActive()){
			$player->sendMessage(TextFormat::RI . "This tournament has ended!");
			return;
		}
		if($response){
			$game->end(true);
			return;
		}
		$player->showModal(new ManageGameUi($player, $game));
	}

}