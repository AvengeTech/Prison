<?php

namespace prison\commands;

use core\AtPlayer;
use core\command\type\CoreCommand;
use core\rank\Rank;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use prison\Prison;
use prison\PrisonPlayer;
use core\utils\TextFormat;

class SaveAllData extends CoreCommand {

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setHierarchy(Rank::HIERARCHY_HEAD_MOD);
		$this->setAliases(["sad"]);
	}

	public function handlePlayer(AtPlayer $sender, string $commandLabel, array $args)
	{
		$this->plugin->saveAll();
		$sender->sendMessage(TextFormat::GN . "Server data has been saved!");
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}