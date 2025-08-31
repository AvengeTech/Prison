<?php namespace prison\enchantments\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\enchantments\uis\enchanter\StaffItemEditorUi;

use core\utils\TextFormat;

class EditItem extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name,$description);
		$this->setPermission("prison.tier3");
		$this->setAliases(["ei"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		/** @var PrisonPlayer $sender */
		if(!$sender instanceof Player){
			$sender->sendMessage("no");
			return false;
		}
		if(!Prison::getInstance()->isTestServer()){
			if(!$sender->isTier3()){
				$sender->sendMessage(TextFormat::RN . "You do not have permission to use this command");
				return false;
			}
		}

		$sender->showModal(new StaffItemEditorUi($sender));
		return true;
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}