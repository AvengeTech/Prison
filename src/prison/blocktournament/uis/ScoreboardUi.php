<?php namespace prison\blocktournament\uis;

use pocketmine\player\Player;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use prison\Prison;
use prison\PrisonPlayer;
use prison\blocktournament\Game;

use core\utils\TextFormat;

class ScoreboardUi extends SimpleForm{

	public $game;

	public $places = [];
	public $page;

	public $prev = false;
	public $next = false;

	public $pp = null;

	public function __construct(Player $player, Game $game, int $page = 1, int $display = 10, $pp = null){
		$this->game = Prison::getInstance()->getBlockTournament()->getGameManager()->getGameFrom($game);
		parent::__construct("Scoreboard", "Tap on a player to view more details.");

		$this->places = $places = $game->getPlaces($display, $page);
		$this->page = $page;

		$this->pp = $pp;

		$i = ($page - 1) * 10 + 1;
		$colors = [
			$i => TextFormat::GREEN,
			$i + 1 => TextFormat::YELLOW,
			$i + 2 => TextFormat::RED
		];
		foreach($places as $u){
			$this->addButton(new Button(($page == 1 ? ($colors[$i] ?? "") : "") . $u->getFormattedPlace() . ". " . TextFormat::DARK_GRAY . $u->getName() . PHP_EOL . "Blocks mined: " . number_format($u->getBlocksMined())));
			$i++;
		}
		if($page !== 1){
			$this->prev = true;
			$this->addButton(new Button("Previous Page (" . ($page - 1) . ")"));
		}
		if(count($places) == 10){
			$this->next = true;
			$this->addButton(new Button("Next Page (" . ($page + 1) . ")"));
		}
		$this->addButton(new Button("Go back"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$places = $this->places;
		if(($score = $places[$response] ?? null) !== null){
			$player->showModal(new MineDetailsUi($this->game, $score, $this));
			return;
		}
		if($response == count($places)){
			if($this->prev){
				$player->showModal(new ScoreboardUi($player, $this->game, $this->page - 1, 10, $this->pp));
				return;
			}
			if($this->next){
				$player->showModal(new ScoreboardUi($player, $this->game, $this->page + 1, 10, $this->pp));
				return;
			}
			if($this->pp !== null){
				$player->showModal($this->pp);
				return;
			}
			$player->showModal(new GameDetailsUi($this->game));
			return;
		}
		if($response == count($places) + 1){
			if($this->prev && $this->next){
				$player->showModal(new ScoreboardUi($player, $this->game, $this->page + 1, 10, $this->pp));
				return;
			}
			if($this->pp !== null){
				$player->showModal($this->pp);
				return;
			}
			$player->showModal(new GameDetailsUi($this->game));
		}
	}

}