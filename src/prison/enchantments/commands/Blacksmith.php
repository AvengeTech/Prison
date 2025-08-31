<?php namespace prison\enchantments\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\enchantments\uis\blacksmith\BlacksmithUi;

class Blacksmith extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["bs"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player) $sender->showModal(new BlacksmithUi($sender));
		return true;
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}