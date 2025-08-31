<?php namespace prison\enchantments\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;
use prison\enchantments\uis\conjuror\ConjurorUI;
use prison\Prison;
use prison\PrisonPlayer;

class Conjuror extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["conj"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player) $sender->showModal(new ConjurorUI($sender));
		return true;
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}