<?php namespace prison\blocktournament\uis;

use pocketmine\player\Player;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use prison\Prison;
use prison\PrisonPlayer;

use core\utils\TextFormat;

class GameModUi extends SimpleForm{

	public $games = [];

	public function __construct(Player $player, $message = ""){
		parent::__construct("BT Mod Menu", ($message !== "" ? $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") . "Select a tournament below to moderate");

		$this->addButton(new Button("Refresh"));
		$gm = Prison::getInstance()->getBlockTournament()->getGameManager();
		foreach($gm->getActiveGames() as $game){
			$this->games[] = $game;
			$this->addButton(new Button($game->getCreatorName() . "'s Tournament" . PHP_EOL . "Status: " . $game->getStatusName() . " | " . ($game->isPrivate() ? TextFormat::RED . "PRIVATE" : TextFormat::GREEN . "PUBLIC")));
		}
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		if(!$player->isStaff()){
			$player->sendMessage(TextFormat::RI . "You cannot use this menu.");
			return;
		}
		if($response == 0){
			$player->showModal(new GameModUi($player));
			return;
		}
		foreach($this->games as $key => $game){
			if($key + 1 == $response){
				$game = Prison::getInstance()->getBlockTournament()->getGameManager()->getGameFrom($game);
				if(!$game->isActive()){
					$player->showModal(new GameModUi($player, TextFormat::RED . "This game is no longer active!"));
					return;
				}

				$player->showModal(new ManageGameUi($player, $game));
				return;
			}
		}
	}

}
