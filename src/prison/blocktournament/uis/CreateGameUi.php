<?php namespace prison\blocktournament\uis;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\blocktournament\Game;

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	StepSlider,
	Label,
	Toggle
};
use core\utils\TextFormat;

class CreateGameUI extends CustomForm{

	public $game;

	public function __construct(Player $player, string $error = ""){
		parent::__construct("Block Tournaments");

		$this->addElement(new Label(($error ? TextFormat::RED . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") . "Select how much you would like this Block Tournament to be worth."));

		$this->game = $game = new Game($player);

		$prize = new StepSlider("Prize", $game->getFormattedPrizes());
		$this->addElement($prize);

		$this->addElement(new Label("Select how long you would like the Tournament to last."));

		$length = new StepSlider("Length", $game->getFormattedLengths());
		$this->addElement($length);

		$this->addElement(new Toggle(TextFormat::BOLD . TextFormat::YELLOW . "(" . TextFormat::RESET . TextFormat::YELLOW . "NEW!" . TextFormat::BOLD . ") " . TextFormat::RESET . TextFormat::WHITE . "Private match"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$game = $this->game;
		$game->setup($response[1], $response[3], ($private = $response[4]));

		$bt = Prison::getInstance()->getBlockTournament();
		$g = $bt->getGameManager()->getPlayerGame($player);
		if($g !== null){
			$player->sendMessage(TextFormat::RI . "You are already in a Block Tournament! Type " . TextFormat::YELLOW . "/bt details" . TextFormat::GRAY . " for more information!");
			return;
		}

		if($private){
			if($player->getRank() == "default"){
				$player->showModal(new CreateGameUi($player, "You must have a rank to start private games!"));
				return;
			}
			$player->showModal(new InvitePlayersUi($player, $game));
			return;
		}
		if($bt->getGameManager()->getPublicGame() !== null){
			$player->showModal(new CreateGameUi($player, "There is already a public tournament open! You can join it with " . TextFormat::YELLOW . "/bt join"));
			return;
		}
		$player->showModal(new ConfirmStartUi($player, $game));
	}

}