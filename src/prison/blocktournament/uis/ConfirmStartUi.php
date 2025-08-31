<?php namespace prison\blocktournament\uis;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\blocktournament\Game;

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class ConfirmStartUi extends ModalWindow{

	public $game;

	public function __construct(Player $player, Game $game){
		/** @var PrisonPlayer $player */
		$this->game = $game;

		$inv = $game->getInvites();
		foreach($inv as $key => $invv){
			$inv[$key] = $invv->getGamertag();
		}

		parent::__construct(
			"Confirm Block Tournament",
			"You are about to start a " . TextFormat::YELLOW . $game->getFormattedLength() . TextFormat::WHITE . " Block Tournament for " . TextFormat::AQUA . number_format($game->getPrize()) . " Techits" . TextFormat::WHITE . ". It will cost you " . TextFormat::AQUA . number_format($game->getStartPrice()) . " Techits" . TextFormat::WHITE . " to start." . PHP_EOL . PHP_EOL . "Join status: " . (($private = $game->isPrivate()) ? "Private" : "Public") . PHP_EOL . ($private ? "Players invited: " . implode(", ", $inv) . PHP_EOL : PHP_EOL) . PHP_EOL . "Select an option below.",
			"Start Tournament", "Go back"
		);
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		if($response){
			$bt = Prison::getInstance()->getBlockTournament();
			$g = $bt->getGameManager()->getPlayerGame($player);
			if($g !== null){
				$player->sendMessage(TextFormat::RI . "You are already in a Block Tournament! Type " . TextFormat::YELLOW . "/bt details" . TextFormat::GRAY . " for more information!");
				return;
			}

			$game = $this->game;
			if(!$game->canAfford($player)){
				$player->showModal(new CreateGameUi($player, "You cannot afford to start this game!"));
				return;
			}

			$player->sendMessage(TextFormat::GI . "Successfully started a " . ($game->isPrivate() ? "private" : "public") . " Block Tournament! Type " . TextFormat::YELLOW . "/bt details" . TextFormat::GRAY . " to view/edit game details.");
			$bt->getGameManager()->addActiveGame($game)->start($player);
			return;
		}
		$player->showModal(new CreateGameUi($player));
	}

}