<?php

namespace prison\commands;

use core\AtPlayer;
use core\command\type\CoreCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use prison\Prison;
use core\utils\TextFormat;
use core\network\Links;
use core\rank\Structure as RS;

class Feed extends CoreCommand {

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name, $description);
		$this->setHierarchy(RS::RANK_HIERARCHY['endermite']);
		$this->setInGameOnly();
	}

	public function handlePlayer(AtPlayer $sender, string $commandLabel, array $args)
	{
		$sender->getHungerManager()->setFood(20);
		$sender->getHungerManager()->setExhaustion(0);
		$sender->getHungerManager()->setSaturation(20);
		$sender->sendMessage(TextFormat::GN . "Your hunger bar has been filled!");
		return true;
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}