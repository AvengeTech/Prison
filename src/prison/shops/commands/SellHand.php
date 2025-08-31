<?php namespace prison\shops\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\utils\TextFormat;
use core\AtPlayer as Player;
use pocketmine\plugin\Plugin;

use prison\Prison;

class SellHand extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name,$description);
		$this->setPermission("prison.perm");
		$this->setAliases(["sh"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if($sender instanceof Player){
			$shops = $this->plugin->getShops();
			return $shops->sellHand($sender);
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}