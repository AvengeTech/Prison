<?php namespace prison\blocktournament\uis;

use pocketmine\player\Player;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use prison\Prison;
use prison\PrisonPlayer;
use prison\blocktournament\Game;

use core\utils\TextFormat;

class ManageGameUi extends SimpleForm{

	public $game;

	public function __construct(Player $player, ?Game $game = null, $message = ""){
		parent::__construct("Manage Tournament", ($message !== "" ? $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") . "Select an option below.");
		$gm = Prison::getInstance()->getBlockTournament()->getGameManager();
		if($game == null) $game = $gm->getPlayerGame($player);
		$game = $this->game = $gm->getGameFrom($game);

		$this->addButton(new Button("Manage Invites (" . count($game->getInvites()) . ")"));
		$this->addButton(new Button("Manage Players (" . count($game->getPlayers()) . ")"));
		$this->addButton(new Button("Cancel Tournament"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$game = Prison::getInstance()->getBlockTournament()->getGameManager()->getGameFrom($this->game);
		if(!$game->canEdit($player)){
			$player->sendMessage(TextFormat::RI . "You do not have permission to edit this tournament!");
			return;
		}
		if(!$game->isActive()){
			$player->sendMessage(TextFormat::RI . "This tournament has ended!");
			return;
		}
		if($response == 0){
			if(!$game->isPrivate()){
				$player->showModal(new ManageGameUi($player, $game, TextFormat::RED . "Not available for public matches."));
				return;
			}
			$player->showModal(new ManageInvitesUi($player, $game));
			return;
		}
		if($response == 1){
			$player->showModal(new ManagePlayersUi($player, $game));
			return;
		}
		$player->showModal(new CancelGameUi($game));
	}

}