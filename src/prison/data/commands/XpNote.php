<?php namespace prison\data\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;
use pocketmine\player\Player;

use prison\Prison;
use prison\data\items\XpNote as XpNoteItem;

use core\utils\TextFormat;

/**
 * @deprecated 1.9.0
 */
class XpNote extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name,$description);
		$this->setPermission("prison.perm");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if($sender instanceof Player){
			$amount = (int)array_shift($args);

			if($amount <= 0){
				$sender->sendMessage(TextFormat::RN . "Amount must be at least 1!");
				return false;
			}

			if($amount > $sender->getXpManager()->getXpLevel()){
				$sender->sendMessage(TextFormat::RN . "You do not have enough XP Levels!");
				return false;
			}

			$item = new XpNoteItem();
			$item->setup($amount);
			if(!$sender->getInventory()->canAddItem($item)){
				$sender->sendMessage(TextFormat::RN . "Your inventory is full!");
				return false;
			}

			$sender->getInventory()->addItem($item);
			$sender->getXpManager()->subtractXpLevels($amount);
			$sender->sendMessage(TextFormat::GN . "XP Note added to your inventory!");

			return true;
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}