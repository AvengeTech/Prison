<?php namespace prison\guards\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;

use core\utils\TextFormat;

class GuardSpawnCommand extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name,$description);
		$this->setPermission("prison.tier3");
		$this->setAliases(["gs"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player && !$sender->isTier3()) return false;
		if(empty($args)){
			$sender->sendMessage(TextFormat::RI . "Usage: /gspawn (path)");
			return false;
		}

		$path = ($g = Prison::getInstance()->getGuards())->getPathManager()->getPath(array_shift($args));
		if($path == null){
			$sender->sendMessage(TextFormat::RI . "Path does not exist!");
			return false;
		}

		$g->spawnGuard($path);
		$sender->sendMessage(TextFormat::GI . "Successfully summoned guard!");
		return true;
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}