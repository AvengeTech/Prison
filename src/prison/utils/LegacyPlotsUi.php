<?php namespace prison\utils;

use pocketmine\{player\GameMode, player\Player, Server};
use pocketmine\world\Position;

use prison\Prison;
use prison\PrisonPlayer;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use core\utils\TextFormat;

class LegacyPlotsUi extends SimpleForm{

	const SEASON_PLOTS = [
		2 => [
			0 => "new_plots",
			1 => "nether_plots",
			2 => "end_plots",
		],
		3 => [
			0 => "s3plots",
			1 => "nether_plotsnew",
			2 => "end_plotsnew",
		],

	];

	public function __construct(public int $season){
		parent::__construct("Legacy plots", "Plots from season " . $season . " can be visited below. Select a legacy plot world you'd like to teleport to! (NOTE: You cannot claim new plots or build in these worlds)");

		switch($season){
			case 1:
				$this->addButton(new Button("Prison 1"));
				$this->addButton(new Button("Prison 2"));
				$this->addButton(new Button("Go back"));
				break;
			default:
				$this->addButton(new Button("Basic"));
				$this->addButton(new Button("Nether"));
				$this->addButton(new Button("End"));
				$this->addButton(new Button("Go back"));
				break;
		}

	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		switch($this->season){
			case 1:
				switch($response){
					case 0:
						if(!Server::getInstance()->getWorldManager()->isWorldLoaded("plots-season1p1")){
							Server::getInstance()->getWorldManager()->loadWorld("plots-season1p1");
						}
						$player->teleport(new Position(32, 56, 32, Server::getInstance()->getWorldManager()->getWorldByName("plots-season1p1")), 0, 0);
						$player->sendMessage(TextFormat::GI . "Teleported to the " . TextFormat::YELLOW . "Season 1 Plots " . TextFormat::GRAY . "world! (prison 1)");
						break;
					case 1:
						if(!Server::getInstance()->getWorldManager()->isWorldLoaded("plots-season1p2")){
							Server::getInstance()->getWorldManager()->loadWorld("plots-season1p2");
						}
						$player->teleport(new Position(32, 56, 32, Server::getInstance()->getWorldManager()->getWorldByName("plots-season1p2")), 0, 0);
						$player->sendMessage(TextFormat::GI . "Teleported to the " . TextFormat::YELLOW . "Season 1 Plots " . TextFormat::GRAY . "world! (prison 2)");
						break;
					case 2:
						$player->showModal(new PlotWorldUi($player));
						return;
				}
				break;
			default:
				switch($response){
					case 0:
						if(!Server::getInstance()->getWorldManager()->isWorldLoaded(self::SEASON_PLOTS[$this->season][0])){
							Server::getInstance()->getWorldManager()->loadWorld(self::SEASON_PLOTS[$this->season][0]);
						}
						$player->teleport(new Position(32, 56, 32, Server::getInstance()->getWorldManager()->getWorldByName(self::SEASON_PLOTS[$this->season][0])), 0, 0);
						$player->sendMessage(TextFormat::GI . "Teleported to the " . TextFormat::YELLOW . "Season " . $this->season . " Basic Plots " . TextFormat::GRAY . "world!");
						break;
					case 1:
						if(!Server::getInstance()->getWorldManager()->isWorldLoaded(self::SEASON_PLOTS[$this->season][1])){
							Server::getInstance()->getWorldManager()->loadWorld(self::SEASON_PLOTS[$this->season][1]);
						}
						$player->teleport(new Position(36, 56, 38, Server::getInstance()->getWorldManager()->getWorldByName(self::SEASON_PLOTS[$this->season][1])), 0, 0);
						$player->sendMessage(TextFormat::GI . "Teleported to the " . TextFormat::YELLOW . "Season " . $this->season . " Nether Plots " . TextFormat::GRAY . "world!");
						break;
					case 2:
						if(!Server::getInstance()->getWorldManager()->isWorldLoaded(self::SEASON_PLOTS[$this->season][2])){
							Server::getInstance()->getWorldManager()->loadWorld(self::SEASON_PLOTS[$this->season][2]);
						}
						$player->teleport(new Position(63.5, 57, 63.5, Server::getInstance()->getWorldManager()->getWorldByName(self::SEASON_PLOTS[$this->season][2])), 0, 0);
						$player->sendMessage(TextFormat::GI . "Teleported to the " . TextFormat::YELLOW . "Season " . $this->season . " End Plots " . TextFormat::GRAY . "world!");
						break;
					case 2:
						$player->showModal(new PlotWorldUi($player));
						return;
				}
				break;
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