<?php namespace prison\shops\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use prison\shops\uis\CategorySelectUi;
use prison\Prison;
use prison\PrisonPlayer;

class BlackMarket extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name,$description);
		$this->setPermission("prison.perm");
		$this->setAliases(["bm","shop"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		/** @var PrisonPlayer $sender */
		$sender->showModal(new CategorySelectUi());
		return true;
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}