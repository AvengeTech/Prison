<?php namespace prison\enchantments\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;
use pocketmine\item\Durable;

use prison\Prison;
use prison\PrisonPlayer;

use core\Core;
use core\utils\TextFormat;

class Repair extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name,$description);
		$this->setPermission("prison.perm");
		$this->setAliases(["fix"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player){
			if(Core::getInstance()->getNetwork()->getServerManager()->getThisServer()->getTypeId() == "event"){
				$sender->sendMessage(TextFormat::RI . "You cannot use /repair during an event!");
				return false;
			}

			$rh = $sender->getRankHierarchy();

			if ($rh < 3) {
				$sender->sendMessage(TextFormat::RN . "You must be at least " . TextFormat::WHITE . TextFormat::BOLD . "GHAST " . TextFormat::RESET . TextFormat::GRAY . "rank to use this command! You may repair your tools at the " . TextFormat::BOLD . TextFormat::DARK_GRAY . "Blacksmith " . TextFormat::RESET . TextFormat::GRAY . "(" . TextFormat::YELLOW . "/blacksmith" . TextFormat::GRAY . "), or purchase a rank! " . TextFormat::YELLOW . "store.avengetech.net");
				return false;
			}

			if(($ench = Prison::getInstance()->getEnchantments())->hasCooldown($sender) && !$sender->isTier3()){
				$cd = $ench->getCooldownFormatted($sender);
				$sender->sendMessage(TextFormat::RI . "You must wait " . TextFormat::WHITE . $cd . TextFormat::GRAY . " to use this again!");
				return false;
			}

			$item = $sender->getInventory()->getItemInHand();
			if(!$item instanceof Durable){
				$sender->sendMessage(TextFormat::RI . "You cannot repair this item!");
				return false;
			}
			if($item->getDamage() == 0){
				$sender->sendMessage(TextFormat::RI . "This item already has maximum durability!");
				return false;
			}

			switch (true) {
				case $rh <= 10:
					$cooldown = 60 * 30;
					break;
				case $rh <= 5:
					$cooldown = 60 * 90;
					break;
				case $rh <= 4:
					$cooldown = 60 * 150;
					break;
				case $rh <= 3:
					$cooldown = 60 * 240;
					break;
				default:
					$cooldown = 0;
					break;
			}
			$ench->setCooldown($sender, $cooldown);

			$item->setDamage(0);
			$sender->getInventory()->setItemInHand($item);
			$sender->sendMessage(TextFormat::GI . "Successfully repaired the item in your hand!");
			return true;
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}