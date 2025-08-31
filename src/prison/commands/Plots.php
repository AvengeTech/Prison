<?php

namespace prison\commands;

use core\AtPlayer;
use core\command\type\CoreCommand;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use prison\Prison;
use prison\PrisonPlayer;
use prison\utils\PlotWorldUi;
use core\utils\TextFormat;

class Plots extends CoreCommand {

	public $plugin;

	public function __construct(Prison $plugin, $name, $description) {
		$this->plugin = $plugin;
		parent::__construct($name, $description);
		$this->setInGameOnly();
	}

	/**
	 * @param PrisonPlayer $sender
	 */
	public function handlePlayer(AtPlayer $sender, string $commandLabel, array $args) {
		if ($sender->isBattleParticipant()) {
			$sender->sendMessage(TextFormat::RI . "You cannot use this command while in a gang battle!");
			return false;
		}
		if (empty($args)) {
			$sender->showModal(new PlotWorldUi($sender));
			return true;
		}
		switch (strtolower(array_shift($args))) {
			default:
				$sender->sendMessage(TextFormat::RI . "Invalid plot world! (" . TextFormat::AQUA . "Basic" . TextFormat::GRAY . ", " . TextFormat::RED . "Nether" . TextFormat::GRAY . ", " . TextFormat::LIGHT_PURPLE . "End" . TextFormat::GRAY . ")");
				return false;
			case "basic":
				$sender->gotoPlotServer(0, TextFormat::GI . "Teleported to the " . TextFormat::YELLOW . "Basic Plots " . TextFormat::GRAY . "world!");
				break;
			case "nether":
				$sender->gotoPlotServer(1, TextFormat::GI . "Teleported to the " . TextFormat::YELLOW . "Nether Plots " . TextFormat::GRAY . "world!");
				break;
			case "end":
				$sender->gotoPlotServer(2, TextFormat::GI . "Teleported to the " . TextFormat::YELLOW . "End Plots " . TextFormat::GRAY . "world!");
				break;
		}

		$session = $sender->getGameSession()->getMines();
		if ($session->inMine()) {
			$session->exitMine(false);
		}
		if ($sender->isBattleSpectator()) {
			$sender->stopSpectating();
		}

		$ksession = $sender->getGameSession()->getKoth();
		if ($ksession->inGame()) {
			$ksession->setGame();
		}

		$sender->setGamemode(\pocketmine\player\GameMode::SURVIVAL());
		if (!$sender->getGameSession()->getCombat()->inPvPMode()) $sender->setAllowFlight(true);
		$sender->removeChildEntities();
	}
}
