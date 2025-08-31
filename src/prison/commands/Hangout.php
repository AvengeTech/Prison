<?php

namespace prison\commands;

use core\AtPlayer;
use core\command\type\CoreCommand;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use pocketmine\world\Position;

use prison\Prison;
use prison\PrisonPlayer;

use core\Core;
use core\utils\TextFormat;
use core\network\protocol\PlayerLoadActionPacket;

class Hangout extends CoreCommand {

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setInGameOnly();
	}

	/**
	 * @param PrisonPlayer $sender
	 */
	public function handlePlayer(AtPlayer $sender, string $commandLabel, array $args)
	{
		$session = $sender->getGameSession()->getMines();
		if($session->inMine()){
			$session->exitMine();
		}elseif($sender->isBattleSpectator()){
			$sender->stopSpectating();
		}
		$ksession = $sender->getGameSession()->getKoth();
		if($ksession->inGame()){
			$ksession->setGame();
		}
		if(($ts = Core::thisServer())->isSubServer()){
			(new PlayerLoadActionPacket([
				"player" => $sender->getName(),
				"server" => "prison-" . $ts->getTypeId(),
				"action" => "hangout",
			]))->queue();
			$sender->gotoSpawn();
		}else{
			$sender->teleport(new Position(-833.5, 24, 383.5, $this->plugin->getServer()->getWorldManager()->getWorldByName("newpsn")), 90, 0);
			$sender->setAllowFlight(true);
			$sender->removeChildEntities();
			$sender->sendMessage(TextFormat::YN . "Teleported to " . TextFormat::AQUA . "The Hangout");
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}