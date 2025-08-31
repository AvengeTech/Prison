<?php namespace prison\shops\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use core\AtPlayer as Player;
use pocketmine\plugin\Plugin;

use prison\Prison;

use core\network\Links;
use core\utils\TextFormat;

class SellAll extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name,$description);
		$this->setPermission("prison.perm");
		$this->setAliases(["sa"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if($sender instanceof Player){
			$rank = $sender->getRank();

			if($rank == "default"){
				$sender->sendMessage(TextFormat::RI . "You need a premium rank to use this command! Purchase one at " . Links::SHOP);
				return false;
			}

			$shops = $this->plugin->getShops();
			return $shops->sellAll($sender);
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}