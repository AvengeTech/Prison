<?php namespace prison\kits\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;

use core\network\Links;
use core\utils\TextFormat;

class KitCommand extends Command{
	
	public function __construct(string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		if($sender instanceof Player){
			if(count($args) != 1){
				$sender->sendMessage(TextFormat::RI . "Usage: /kit <name>");
				$sender->sendMessage(Prison::getInstance()->getKits()->getKitListString($sender));
				return;
			}
			$kitName = strtolower(array_shift($args));
			if ($kitName == "ed") $kitName = "enderdragon";
			$kit = Prison::getInstance()->getKits()->getKit($kitName);
			if($kit === null){
				$sender->sendMessage(TextFormat::RI . "Invalid kit!");
				$sender->sendMessage(Prison::getInstance()->getKits()->getKitListString($sender));
				return;
			}

			if(!$kit->hasRequiredRank($sender)){
				$sender->sendMessage(TextFormat::RI . "You must have at least " . $kit->getRank() . " rank to use this kit! Purchase a rank at " . TextFormat::YELLOW . Links::SHOP);
				return;
			}

			/** @var PrisonPlayer $sender */
			$session = $sender->getGameSession()->getKits();
			if(!$sender->isTier3() && $session->hasCooldown($kit->getName())){
				$sender->sendMessage(TextFormat::RI . "You have a cooldown on this kit! Next use: " . TextFormat::WHITE . $session->getFormattedCooldown($kit->getName()));
				return;
			}

			$kit->equip($sender);
			$sender->sendMessage(TextFormat::GI . "Successfully equipped the " . $kit->getName() . " kit.");
		}
	}

}