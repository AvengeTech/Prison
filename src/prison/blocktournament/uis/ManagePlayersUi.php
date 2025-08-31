<?php namespace prison\blocktournament\uis;

use pocketmine\player\Player;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use prison\Prison;
use prison\PrisonPlayer;
use prison\blocktournament\Game;

use core\utils\TextFormat;

class ManagePlayersUi extends SimpleForm{

	public $game;
	public $players = [];

	public function __construct(Player $player, ?Game $game = null, $message = ""){
		parent::__construct("Manage Invites", ($message !== "" ? $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") . "Select an option below.");

		$gm = Prison::getInstance()->getBlockTournament()->getGameManager();
		if($game == null) $game = $gm->getPlayerGame($player);
		$game = $this->game = $gm->getGameFrom($game);

		foreach($game->getPlayers() as $pl){
			$this->players[] = $pl;
			$this->addButton(new Button($pl->getName() . " - " . $pl->getBlocksMined() . " mined" . PHP_EOL . "Tap to kick player"));
		}
		$this->addButton(new Button("Go back"));
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
		foreach($this->players as $key => $pl){
			if($key == $response){
				$p = $pl->getPlayer();
				if($p instanceof Player){
					if(!$game->inCompetition($p)){
						$player->showModal(new ManagePlayersUi($player, $game, TextFormat::RED . "Player not found in tournament."));
						return;
					}
					$game->removePlayer($p);
					$p->sendMessage(TextFormat::RI . "You have been kicked from the Block Tournament!");
					$player->showModal(new ManagePlayersUi($player, $game, TextFormat::GREEN . "Successfully removed " . $p->getName() . " from the game."));
					return;
				}
				if(($place = $game->getPlace($pl->getXuid())) !== 0){
					unset(Prison::getInstance()->getBlockTournament()->getGameManager()->active[$game->getId()]->players[$place - 1]);
					$player->showModal(new ManagePlayersUi($player, $game, TextFormat::GREEN . "Successfully removed " . $pl->getName() . " from the game."));
					return;
				}
				$player->showModal(new ManagePlayersUi($player, $game, TextFormat::RED . "An error occured when trying to remove this player."));
				return;
			}
		}
		$player->showModal(new ManageGameUi($player, $game));
	}

}
