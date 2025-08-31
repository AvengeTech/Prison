<?php namespace prison\enchantments\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;
use pocketmine\item\Durable;

use prison\Prison;
use prison\PrisonPlayer;
use prison\enchantments\uis\SignItemUi;
use prison\enchantments\ItemData;

use core\utils\TextFormat;

class Sign extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name,$description);
		$this->setPermission("prison.perm");
		$this->setAliases(["signature"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player){
			$item = $sender->getInventory()->getItemInHand();
			$data = new ItemData($item);
			if($data->isSigned()){
				$sender->sendMessage(TextFormat::RI . "This item is already signed!");
				return true;
			}
			if(!$item instanceof Durable){
				$sender->sendMessage(TextFormat::RI . "You can only sign tools, weapons or armor! Please hold the item you would like to sign and use this command again.");
				return true;
			}
			$sender->showModal(new SignItemUi($item));
			return false;
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}