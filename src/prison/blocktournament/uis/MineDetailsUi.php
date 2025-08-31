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

class MineDetailsUi extends SimpleForm{

	public $game;
	public $score;

	public $prev = null;

	public function __construct(Game $game, PlayerScore $score, $prev = null){
		$game = $this->game = Prison::getInstance()->getBlockTournament()->getGameManager()->getGameFrom($game);
		$this->score = $score;

		$this->prev = $prev;

		$details = $score->getMineDetails();
		$text = "Place: " . $score->getFormattedPlace() . PHP_EOL . PHP_EOL . "Total blocks mined: " . number_format($score->getBlocksMined()) . PHP_EOL . "Breakdown:" . PHP_EOL;
		foreach($details as $mine => $total){
			$text .= " - " . strtoupper($mine) . ": " . number_format($total) . PHP_EOL;
		}
		parent::__construct($score->getName() . "'s Entry", $text);
		$this->addButton(new Button("Refresh"));
		$this->addButton(new Button("Go back"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$game = Prison::getInstance()->getBlockTournament()->getGameManager()->getGameFrom($this->game);
		if($game == null){
			$player->sendMessage(TextFormat::RI . "Game could not be found.");
			return;
		}
		if($response == 0){
			$player->showModal(new MineDetailsUi($game, $game->getUpdatedScore($this->score), $this->prev));
			return;
		}
		if($this->prev !== null){
			$player->showModal($this->prev);
			return;
		}
		$player->showModal(new GameDetailsUi($game));
	}

}
