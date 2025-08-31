<?php namespace prison\enchantments\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\enchantments\uis\aguide\{
	AnimatorGuideUi,
	ShowGuideUi
};

use core\utils\TextFormat;

class AnimatorGuide extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["aguide", "ag"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		/** @var PrisonPlayer $sender */
		if(!$sender instanceof Player){
			$sender->sendMessage(TextFormat::RI . "no");
			return false;
		}
		if(empty($args)){
			$sender->showModal(new AnimatorGuideUi($sender));
			return true;
		}
		$eff = Prison::getInstance()->getEnchantments()->getEffects()->getEffectByName(array_shift($args), $sender->isStaff());
		if($eff === null){
			$sender->sendMessage(TextFormat::RI . "Invalid animator name provided!");
			return false;
		}
		$sender->showModal(new ShowGuideUi($eff, false));
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}