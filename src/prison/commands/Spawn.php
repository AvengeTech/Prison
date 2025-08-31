<?php

namespace prison\commands;

use core\AtPlayer;
use core\command\type\CoreCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;

use core\utils\TextFormat;

class Spawn extends CoreCommand {

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setInGameOnly();
	}

	/**
	 * @param PrisonPlayer $sender
	 */
	public function handlePlayer(AtPlayer $sender, string $commandLabel, array $args) {
		$session = ($gs = $sender->getGameSession())->getMines();
		if ($session->inMine()) {
			$session->exitMine(false);
		} elseif ($sender->isBattleSpectator()) {
			$sender->stopSpectating();
		}
		$sender->gotoSpawn();

		$ksession = $sender->getGameSession()->getKoth();
		if ($ksession->inGame()) {
			$ksession->setGame();
		}

		$sender->setGamemode(\pocketmine\player\GameMode::ADVENTURE());
		if (!$gs->getCombat()->inPvPMode()) $sender->setAllowFlight(true);

		$sender->removeChildEntities();
		$sender->sendMessage(TextFormat::YN . "Teleported to spawn!");
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}