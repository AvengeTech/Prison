<?php namespace prison\utils;

use pocketmine\Server;
use pocketmine\player\{
	GameMode,
	Player
};
use pocketmine\world\Position;

use prison\Prison;
use prison\PrisonPlayer;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class PlotWorldUi extends SimpleForm{

	public function __construct(Player $player, string $error = ""){
		parent::__construct("Plots", ($error != "" ? TextFormat::RED . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") . "Select a plot world you'd like to teleport to!");

		$this->addButton(new Button("Basic Plots" . PHP_EOL . TextFormat::ITALIC . "Old fashioned plot world"));
		$this->addButton(new Button("Nether Plots" . PHP_EOL . TextFormat::ITALIC . "From the darkest depths"));
		$this->addButton(new Button("End Plots" . PHP_EOL . TextFormat::ITALIC . "Plots out of this world!"));
		//$this->addButton(new Button("Season 1 Plots" . PHP_EOL . TextFormat::ITALIC . "Time to reminisce"));
		//$this->addButton(new Button("Season 2 Plots" . PHP_EOL . TextFormat::ITALIC . "Time to reminisce"));
		//$this->addButton(new Button("Season 3 Plots" . PHP_EOL . TextFormat::ITALIC . "Time to reminisce (yeah)"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$session = ($gs = $player->getGameSession())->getRankUp();
		if($gs->getCombat()->isTagged()){
			$player->sendMessage(TextFormat::RI . "You cannot teleport to a plot world while in combat.");
			return;
		}
		switch($response){
			case 0:
				$player->gotoPlotServer(0, TextFormat::GI . "Teleported to the " . TextFormat::YELLOW . "Basic Plots " . TextFormat::GRAY . "world!");
				break;
			case 1:
				$player->gotoPlotServer(1, TextFormat::GI . "Teleported to the " . TextFormat::YELLOW . "Nether Plots " . TextFormat::GRAY . "world!");
				break;
			case 2:
				$player->gotoPlotServer(2, TextFormat::GI . "Teleported to the " . TextFormat::YELLOW . "End Plots " . TextFormat::GRAY . "world!");
				break;
			/**case 3:
				$player->showModal(new LegacyPlotsUi(2));
				return;
			case 4:
				$player->showModal(new LegacyPlotsUi(3));
				return;*/
		}

		$mines = Prison::getInstance()->getMines();
		$session = $player->getGameSession()->getMines();
		if($session->inMine()){
			$session->exitMine(false);
		}
		if($player->isBattleSpectator()){
			$player->stopSpectating();
		}

		$ksession = $player->getGameSession()->getKoth();
		if($ksession->inGame()){
			$ksession->setGame();
		}

		$player->setGamemode(GameMode::SURVIVAL());
		if(!$player->getGameSession()->getCombat()->inPvPMode()) $player->setAllowFlight(true);
		$player->removeChildEntities();
	}

}