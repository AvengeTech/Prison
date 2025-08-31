<?php namespace prison\blocktournament\uis;

use pocketmine\player\Player;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use core\utils\TextFormat;

use prison\PrisonPlayer;
use prison\blocktournament\Game;

class GameDetailsUi extends SimpleForm{

	public $game;

	public function __construct(Game $game){
		$this->game = $game;

		$by = $game->getCreatorName();
		$prize = number_format($game->getPrize()) . " Techits";
		$length = $game->getFormattedLength();

		$status = $game->isPrivate() ? TextFormat::RED . "PRIVATE" : TextFormat::GREEN . "PUBLIC";

		$participants = count($game->getPlayers());

		parent::__construct(
			"Block Tournament Details",
			"This is the current block tournament you are in." . PHP_EOL . PHP_EOL .

			"Created by: " . TextFormat::YELLOW . $by . TextFormat::WHITE . PHP_EOL .
			"Winning prize: " . TextFormat::AQUA . $prize . TextFormat::WHITE . PHP_EOL .
			"Duration: " . TextFormat::GREEN . $length . TextFormat::WHITE . PHP_EOL . PHP_EOL .

			"Status: " . $status . TextFormat::WHITE . PHP_EOL . 
			"Total participants: " . TextFormat::GREEN . $participants . TextFormat::WHITE .
			($game->isStarted() ? PHP_EOL . PHP_EOL . "Time left: " . TextFormat::GREEN . $game->getFormattedLength($game->timer, 1) . TextFormat::WHITE : "")
		);

		$this->addButton(new Button("Drop out"));
		if($game->isStarted()){
			$this->addButton(new Button("View Scoreboard"));
		}
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		if($response == 0){
			$player->showModal(new DropoutConfirmUi());
			return;
		}
		if($response == 1){
			$player->showModal(new ScoreboardUi($player, $this->game));
		}
	}

}
