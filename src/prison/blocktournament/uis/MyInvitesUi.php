<?php namespace prison\blocktournament\uis;

use pocketmine\player\Player;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use prison\Prison;
use prison\PrisonPlayer;
use prison\blocktournament\{
	Game,
	PlayerScore
};

use core\utils\TextFormat;

class MyInvitesUi extends SimpleForm{

	public $games = [];

	public function __construct(Player $player, $error = ""){
		parent::__construct("My Invites", ($error !== "" ? TextFormat::RED . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") . "Select an invite below to view it's details!");
		$gm = Prison::getInstance()->getBlockTournament()->getGameManager();
		foreach($gm->getJoinableGames($player) as $game){
			if($game->isPrivate()){
				$this->games[] = $game;
				$this->addButton(new Button($game->getCreatorName() . "'s game" . PHP_EOL . count($game->getPlayers()) . " players joined" . PHP_EOL . "Status: " . $game->getStatusName()));
			}
		}
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$bt = Prison::getInstance()->getBlockTournament();
		$game = $bt->getGameManager()->getPlayerGame($player);
		if($game !== null){
			$player->sendMessage(TextFormat::RI . "You are already in a Block Tournament!");
			return;
		}
		foreach($this->games as $key => $game){
			if($response == $key){
				$game = $bt->getGameManager()->getGameFrom($game);
				if(!$game->isInvited($player)){
					$player->showModal(new MyInvitesUi($player, "Your invite to this game has expired or been revoked!"));
					return;
				}
				$player->showModal(new ViewInviteUi($player, $game));
			}
		}
	}

}
