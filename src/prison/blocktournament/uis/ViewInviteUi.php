<?php namespace prison\blocktournament\uis;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\blocktournament\Game;

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class ViewInviteUi extends ModalWindow{

	public $game;

	public function __construct(Player $player, Game $game){
		/** @var PrisonPlayer $player */
		$this->game = $game;

		parent::__construct(
			"Invite",
			"Tournament details:" . PHP_EOL . PHP_EOL . 
			"Creator: " . $game->getCreatorName() . PHP_EOL .
			"Tournament length: " . $game->getFormattedLength() . PHP_EOL .
			"Winning Prize: " . number_format($game->getPrize()) . " techits" . PHP_EOL . PHP_EOL .

			"Players: " . count($game->getPlayers()) . PHP_EOL .
			"Game status: " . $game->getStatusName() . PHP_EOL . PHP_EOL .
			
			"Select an option below.",
			"Accept Invite", "Decline Invite"
		);
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$bt = Prison::getInstance()->getBlockTournament();
		$g = $bt->getGameManager()->getPlayerGame($player);
		if($g !== null){
			$player->sendMessage(TextFormat::RI . "You are already in a Block Tournament! Type " . TextFormat::YELLOW . "/bt details" . TextFormat::GRAY . " for more information!");
			return;
		}

		$game = $bt->getGameManager()->getGameFrom($this->game);
		if(!$game->isInvited($player)){
			$player->showModal(new MyInvitesUi($player, "Your invite was revoked from this tournament!"));
			return;
		}

		if($response){
			$game->addPlayer($player);
			$player->sendMessage(TextFormat::GI . "Successfully joined this " . ($game->isPrivate() ? "private" : "public") . " Block Tournament! Type " . TextFormat::YELLOW . "/bt details" . TextFormat::GRAY . " to view game details.");
			return;
		}
		$game->removeInvite($player);
		$player->showModal(new MyInvitesUi($player, "Successfully declined invite!"));
	}

}