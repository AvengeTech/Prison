<?php

namespace prison\commands;

use core\AtPlayer;
use core\command\type\CoreCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use prison\Prison;
use prison\PrisonPlayer;
use core\utils\TextFormat;
use core\rank\Structure as RS;
use pocketmine\player\GameMode as PlayerGameMode;

class Gamemode extends CoreCommand {

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name, $description);
		$this->setHierarchy(RS::RANK_HIERARCHY['ghast']);
		$this->setAliases(["gm0", "gm1"]);
		$this->setInGameOnly();
	}

	public function handlePlayer(AtPlayer $sender, string $commandLabel, array $args)
	{
		if($sender->getWorld()->getDisplayName() !== "plots"){
			$sender->sendMessage(TextFormat::RI . "This command can only be used in the plot world!");
			return false;
		}
		if(count($args) == 0){
			switch(strtolower($commandLabel)){
				case "gm1":
					if($sender->getGamemode() == 1){
						$sender->sendMessage(TextFormat::RI . "You are already in creative!");
						return false;
					}
					$sender->setGamemode(PlayerGameMode::CREATIVE);
					$sender->sendMessage(TextFormat::GI . "Gamemode set to creative");
					return true;
				case "gm0":
					if($sender->getGamemode() == 0 || $sender->getGamemode() == 1){
						$sender->sendMessage(TextFormat::RI . "You are already in survival!");
						return false;
					}
					$sender->setGamemode(PlayerGameMode::SURVIVAL);
					$sender->sendMessage(TextFormat::GI . "Gamemode set to survival");
					return true;
				default:
					$sender->sendMessage(TextFormat::RI . "Usage: /gm<0:1>");
					return false;
			}
		} else {
			$gm = match ((int)array_shift($args)) {
				0 => PlayerGameMode::SURVIVAL,
				1 => PlayerGameMode::CREATIVE,
				2 => PlayerGameMode::ADVENTURE,
				3 => PlayerGameMode::SPECTATOR,
				default => null
			};
			if (is_null($gm)) {
				$sender->sendMessage(TextFormat::RI . "Invalid gamemode!");
				return;
			}
			if ($sender->getGamemode() == $gm) {
				$sender->sendMessage(TextFormat::RI . "You are already in " . strtolower($gm->getEnglishName()) . "!");
				return;
			}
			$sender->setGamemode($gm);
			$sender->sendMessage(TextFormat::GI . "Gamemode set to " . strtolower($gm->getEnglishName()));
		}
		return;
	}
}