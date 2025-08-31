<?php namespace prison\enchantments\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\enchantments\uis\guide\{
	EnchantGuideUi,
	ShowGuideUi
};

use core\utils\TextFormat;

class Guide extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["eguide", "eg", "enchantguide"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		/** @var PrisonPlayer $sender */
		if(!$sender instanceof Player){
			$sender->sendMessage(TextFormat::RI . "no");
			return false;
		}
		if(empty($args)){
			$sender->showModal(new EnchantGuideUi($sender));
			return true;
		}
		$ench = Prison::getInstance()->getEnchantments()->getEnchantmentByName(array_shift($args), $sender->isStaff());
		if($ench === null){
			$sender->sendMessage(TextFormat::RI . "Invalid enchantment name provided!");
			return false;
		}
		$sender->showModal(new ShowGuideUi($ench, false));
		return true;
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}