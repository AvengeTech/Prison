<?php namespace prison\trash\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;

use prison\Prison;

use core\utils\TextFormat;

class OpenTrash extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name,$description);
		$this->setPermission("prison.perm");
		$this->setAliases(["ot", "t", "trash"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if($sender instanceof Player){
			$id = array_shift($args);
			if($id == null) $id = 1;
			if($id < 1 || $id > 3){
				$sender->sendMessage(TextFormat::RI . "Invalid trash ID! (1-3)");
				return false;
			}

			$this->plugin->getTrash()->open($sender, $id);
			return true;
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}