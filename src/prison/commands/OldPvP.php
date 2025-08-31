<?php

namespace prison\commands;

use core\AtPlayer;
use core\command\type\CoreCommand;
use core\rank\Rank;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\world\Position;
use prison\Prison;
use prison\PrisonPlayer;
use core\utils\TextFormat;

class OldPvP extends CoreCommand {

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name, $description);
		$this->setHierarchy(Rank::HIERARCHY_HEAD_MOD);
		$this->setInGameOnly();
	}

	public function handlePlayer(AtPlayer $sender, string $commandLabel, array $args)
	{
		Server::getInstance()->getWorldManager()->loadWorld("pvpmine", true);
		$sender->teleport(new Position(153.5,18,85.5, Server::getInstance()->getWorldManager()->getWorldByName("pvpmine")));
		$sender->sendMessage("Papi chulo");
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}