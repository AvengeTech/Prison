<?php namespace prison\combat\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;

use core\utils\TextFormat;

class PvP extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["combat"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player){
			$session = $sender->getGameSession()->getCombat();
			if($session->inPvPMode()){
				$sender->sendMessage(TextFormat::RI . "PvP is now disabled!");
			}else{
				$sender->sendMessage(TextFormat::RI . "PvP is now enabled!");
			}
			$session->togglePvPMode();
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}