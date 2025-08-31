<?php namespace prison\blocktournament\uis;

use pocketmine\player\Player;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use core\utils\TextFormat;

use prison\PrisonPlayer;

class GameResultsUi extends SimpleForm{

	public $game;

	public function __construct(Player $player){
		/** @var PrisonPlayer $player */
		$game = $this->game = $player->getGameSession()->getBlockTournament()->getLastGame();
		if($game == null) return;

		$by = $game->getCreatorName();
		$prize = number_format($game->getPrize()) . " Techits";
		$length = $game->getFormattedLength();

		$status = $game->isPrivate() ? TextFormat::RED . "PRIVATE" : TextFormat::GREEN . "PUBLIC";

		$participants = count($game->getPlayers());

		$winner = $game->getWinner();
		if($winner == null){
			$wn = "NONE";
		}else{
			$wn = $winner->getGamertag();
		}

		parent::__construct(
			"Block Tournament Results",
			"This is the last block tournament you participated in." . PHP_EOL . PHP_EOL .

			"Created by: " . TextFormat::YELLOW . $by . TextFormat::WHITE . PHP_EOL .
			"Winning prize: " . TextFormat::AQUA . $prize . TextFormat::WHITE . PHP_EOL .
			"Duration: " . TextFormat::GREEN . $length . TextFormat::WHITE . PHP_EOL . PHP_EOL .

			"Status: " . $status . TextFormat::WHITE . PHP_EOL . 
			"Total participants: " . TextFormat::GREEN . $participants . TextFormat::WHITE . PHP_EOL . PHP_EOL .
			"Winner: " . TextFormat::YELLOW . $wn
		);

		$this->addButton(new Button("View Scoreboard"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		if($response == 0){
			$player->showModal(new ScoreboardUi($player, $this->game, 1, 10, $this));
		}
	}

}
