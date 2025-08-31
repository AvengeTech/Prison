<?php namespace prison\enchantments\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;

use core\utils\TextFormat;
use prison\enchantments\uis\conjuror\EssenceGuideUI;

class EssenceGuide extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["esguide", "esg"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		/** @var PrisonPlayer $sender */
		if(!$sender instanceof Player){
			$sender->sendMessage(TextFormat::RI . "no");
			return false;
		}

		$sender->showModal(new EssenceGuideUI($sender));
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}