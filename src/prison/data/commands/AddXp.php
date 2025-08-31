<?php namespace prison\data\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;
use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;

use core\utils\TextFormat;

class AddXp extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name,$description);
		$this->setPermission("prison.tier3");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player){
			if(!$sender->isTier3()){
				$sender->sendMessage(TextFormat::RN . "You cannot use this command.");
				return false;
			}
		}

		if(count($args) != 2){
			$sender->sendMessage(TextFormat::RN . "Usage: /addxp <player> <amount>");
			return false;
		}

		$player = $this->plugin->getServer()->getPlayerExact(array_shift($args));
		$amount = (int) array_shift($args);

		if(!$player instanceof Player){
			$sender->sendMessage(TextFormat::RN . "Player not found!");
			return false;
		}

		if($amount <= 0){
			$sender->sendMessage(TextFormat::RN . "Amount must be at least 1!");
			return false;
		}

		$player->getXpManager()->addXpLevels($amount);
		$sender->sendMessage(TextFormat::GN . "Successfully gave " . $player->getName() . " " . $amount . " XP Levels");
		$player->sendMessage(TextFormat::GN . "You have received " . TextFormat::YELLOW . $amount . " XP Levels");

		return true;
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}