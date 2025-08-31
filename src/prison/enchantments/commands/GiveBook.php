<?php namespace prison\enchantments\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;
use pocketmine\item\enchantment\Enchantment as PME;

use prison\Prison;
use prison\PrisonPlayer;
use prison\enchantments\ItemData;
use prison\enchantments\type\Enchantment;
use prison\enchantments\EnchantmentData as ED;

use core\utils\TextFormat;

class GiveBook extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name,$description);
		$this->setPermission("prison.tier3");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		/** @var SkyBlockPlayer $sender */
		if(!$sender instanceof Player) return;

		if(!Prison::getInstance()->isTestServer()){
			if(!$sender->isTier3()){
				$sender->sendMessage(TextFormat::RN . "You do not have permission to use this command");
				return;
			}
		}

		if(count($args) < 1){
			$sender->sendMessage(TextFormat::RN . "Usage: /givebook <id> <level>");
			return;
		}

		$ench = array_shift($args);
		if(is_numeric($ench)){
			$enchantment = $this->plugin->getEnchantments()->getEnchantment($ench);
		}else{
			$enchantment = $this->plugin->getEnchantments()->getEnchantmentByName($ench);
		}
		if(!$enchantment instanceof Enchantment){
			$sender->sendMessage(TextFormat::RN . "Invalid enchantment provided!");
			return;
		}


		$max = $enchantment->getMaxLevel();
		$level = (empty($args) ? $max : (int) array_shift($args));
		if($level <= 0){
			$sender->sendMessage(TextFormat::RN . "Level must be between 1-" . $max . "!");
			return;
		}
		$enchantment->setStoredLevel($level);

		$sender->getInventory()->addItem($enchantment->asBook());

		$sender->sendMessage(TextFormat::GI . "You were given a " . $enchantment->getName() . " " . $enchantment->getStoredLevel() . " book!");
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}