<?php namespace prison\koth\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

use prison\Prison;

use core\utils\TextFormat;
use prison\PrisonPlayer;

class KothCommand extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		/** @var PrisonPlayer $sender */
		$koth = Prison::getInstance()->getKoth();

		if(count($args) == 0){
			$sender->sendMessage(TextFormat::RI . "Usage: /koth tp");
			return;
		}

		$active = $koth->getActiveGames();

		$action = strtolower(array_shift($args));
		switch($action){
			case "teleport":
			case "go":
			case "goto":
			case "tp":
				if(count($active) === 0){
					$sender->sendMessage(TextFormat::RI . "No KOTH event is active.");
					return;
				}
				$session = $sender->getGameSession()->getKoth();
				/**if($session->hasCooldown()){
					$sender->sendMessage(TextFormat::RI . "You have recently won a KOTH match! You can participate in another one in " . TextFormat::WHITE . $session->getFormattedCooldown());
					return;
				}*/
				if(count($active) == 1){
					foreach($active as $game){
						$game->teleportTo($sender);
						return;
					}
					return;
				}
				$arena = strtolower(array_shift($args));
				if($arena === null){
					$sender->sendMessage(TextFormat::RI . "Usage: /koth tp <name>");
					foreach($active as $game){
						$sender->sendMessage(TextFormat::GRAY . "- " . TextFormat::AQUA . $game->getName());
					}
					return;
				}
				foreach($active as $game){
					if(strtolower($game->getName()) == $arena){
						$game->teleportTo($sender);
						return;
					}
				}
				$sender->sendMessage(TextFormat::RI . "Invalid arena provided! /koth tp <arena>");
				foreach($active as $game){
					$sender->sendMessage(TextFormat::GRAY . "- " . TextFormat::AQUA . $game->getName());
				}
				return;
			case "start":
				if($sender instanceof Player){
					if(!$sender->isTier3()){
						$sender->sendMessage(TextFormat::RI . "No permission!");
						return;
					}
				}
				$arena = strtolower(array_shift($args));
				if($arena === null){
					$sender->sendMessage(TextFormat::RI . "Usage: /koth start <arena>");
					foreach($koth->getGames() as $match){
						$sender->sendMessage(TextFormat::GRAY . "- " . TextFormat::AQUA . $match->getName());
					}
					return;
				}
				$game = $koth->getGameByName($arena);
				if($game === null){
					$sender->sendMessage(TextFormat::RI . "Invalid arena provided! /koth start <arena>");
					foreach($koth->getGames() as $game){
						$sender->sendMessage(TextFormat::GRAY . "- " . TextFormat::AQUA . $game->getName());
					}
					return;
				}
				if($game->isActive()){
					$sender->sendMessage(TextFormat::RI . "This game is already active!");
					return;
				}
				$koth->startKoth($game->getId());
				break;
			case "stop":
			case "end":
				if($sender instanceof Player){
					if(!$sender->isTier3()){
						$sender->sendMessage(TextFormat::RI . "No permission!");
						return;
					}
				}
				$arena = strtolower(array_shift($args));
				if($arena === null){
					$sender->sendMessage(TextFormat::RI . "Usage: /koth end <arena>");
					foreach($koth->getActiveGames() as $game){
						$sender->sendMessage(TextFormat::GRAY . "- " . TextFormat::AQUA . $game->getName());
					}
					return;
				}
				$game = $koth->getGameByName($arena);
				if($game === null){
					$sender->sendMessage(TextFormat::RI . "Invalid arena provided! /koth start <arena>");
					foreach($koth->getGames() as $game){
						$sender->sendMessage(TextFormat::GRAY . "- " . TextFormat::AQUA . $game->getName());
					}
					return;
				}
				if(!$game->isActive()){
					$sender->sendMessage(TextFormat::RI . "This match is not active!");
					return;
				}
				$game->end(true);
				$sender->getServer()->broadcastMessage(TextFormat::GI . TextFormat::LIGHT_PURPLE . "KOTH match " . TextFormat::YELLOW . $game->getName() . TextFormat::LIGHT_PURPLE . " has been force ended.");
				break;
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}