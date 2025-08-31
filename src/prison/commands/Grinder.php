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

class Grinder extends CoreCommand {

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
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
				"action" => "grinder",
			]))->queue();
			$sender->gotoSpawn();
		}else{
			$sender->teleport(new Position(-777.5, 24, 383.5, $this->plugin->getServer()->getWorldManager()->getWorldByName("newpsn")), 270, 0);
			$sender->setAllowFlight(true);
			$sender->removeChildEntities();
			$sender->sendMessage(TextFormat::YN . "Teleported to " . TextFormat::YELLOW . "The Grinder");
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}