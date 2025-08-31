<?php namespace prison\blocktournament;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\blocktournament\commands\BtCommand;

use core\utils\TextFormat;

class BlockTournament{

	public GameManager $gameManager;

	public function __construct(public Prison $plugin){
		$this->gameManager = new GameManager();

		$plugin->getServer()->getCommandMap()->register("bt", new BtCommand($plugin, "bt", "Start Block Tournaments!"));
	}

	public function getGameManager() : GameManager{
		return $this->gameManager;
	}

	public function tick() : void{
		$this->getGameManager()->tick();
	}

	public function close() : void{
		foreach($this->getGameManager()->getActiveGames() as $game){
			$game->end(true);
		}
	}

	public function onJoin(Player $player) : void {
		/** @var PrisonPlayer $player */
		$session = $player->getGameSession()->getBlockTournament();
		$pg = $this->getGameManager()->getPublicGame();
		if($pg !== null && !$pg->inCompetition($player)){
			if($session->autoJoins()){
				$pg->addPlayer($player);
				$player->sendMessage(TextFormat::YI . "You have been automatically added to the public Block Tournament! Type " . TextFormat::YELLOW . "/bt details" . TextFormat::GRAY . " for more information!");
			}else{
				$player->sendMessage(TextFormat::YI . "There is a public Block Tournament going on right now! Type " . TextFormat::YELLOW . "/bt join " . TextFormat::GRAY . "to enter!");
			}
		}
		$count = 0;
		foreach($this->getGameManager()->getJoinableGames($player) as $g) if($g->isPrivate()) $count++;
		if($count > 0){
			$player->sendMessage(TextFormat::YI . "You have " . TextFormat::AQUA . $count . TextFormat::GRAY . " private Block Tournament invites! Type " . TextFormat::YELLOW . "/bt invites " . TextFormat::GRAY . "to view them!");
		}
	}

}