<?php namespace prison\enchantments\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;
use pocketmine\item\enchantment\Enchantment as PMEnch;

use prison\Prison;
use prison\PrisonPlayer;
use prison\enchantments\{
	EnchantmentData
};
use prison\enchantments\type\Enchantment;

use core\utils\TextFormat;

class AddEnchant extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name,$description);
		$this->setPermission("prison.tier3");
		$this->setAliases(["addench"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		/** @var PrisonPlayer $sender */
		if(!Prison::getInstance()->isTestServer()){
			if(!$sender->isTier3()){
				$sender->sendMessage(TextFormat::RN . "You do not have permission to use this command");
				return false;
			}
		}

		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::RN . "Usage: /addenchantment <id> <level>");
			return;
		}

		$enches = explode(", ", implode(" ", $args));

		$aa = [];
		foreach ($enches as $ee) {
			$eee = explode(" ", $ee);
			if (empty($eee)) continue;

			$name = "";
			$level = -1;
			while (count($eee) > 0) {
				$next = array_shift($eee);
				if (is_numeric($next)) {
					$level = (int) $next;
				} else {
					$name .= $next . " ";
				}
			}
			$name = rtrim($name);

			if ($level == 0) continue;

			$ench = Prison::getInstance()->getEnchantments()->getEnchantmentByName($name);
			if ($ench === null) continue;

			$ench->setStoredLevel(($level == -1 ? $ench->getMaxLevel() : $level), true);
			$aa[] = $ench;
		}

		if (count($aa) === 0) {
			$sender->sendMessage(TextFormat::RI . "Invalid enchantments provided");
			return;
		}
		$item = $sender->getInventory()->getItemInHand();
		$data = Prison::getInstance()->getEnchantments()->getItemData($item);

		$txt = "";
		$invalid = "";
		foreach ($aa as $a) {
			if (!EnchantmentData::canEnchantWith($item, $a)) {
				$invalid .= $a->getName() . ", ";
			} else {
				$data->addEnchantment($a, $a->getStoredLevel());
				$txt .= $a->getName() . ", ";
			}
		}

		$sender->getInventory()->setItemInHand($data->getItem());
		$sender->sendMessage(TextFormat::GI . "Item in hand enchanted! (Added: " . $txt . ")" . (strlen($invalid) !== 0 ? " (Unable to add: " . $invalid . ")" : ""));
		return true;
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}