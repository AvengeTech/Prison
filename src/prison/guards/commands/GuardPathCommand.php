<?php namespace prison\guards\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;
use prison\guards\Path;

use core\utils\TextFormat;

class GuardPathCommand extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.tier3");
		$this->setAliases(["gp"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		/** @var PrisonPlayer $sender */
		if(!$sender instanceof Player || !$sender->isTier3()) return;
		if(count($args) == 0){
			$sender->sendMessage(TextFormat::RI . "Usage: /gpath [create <name> [loops=false] or [/gpath <set:done:nvm>]");
			return;
		}

		$gu = Prison::getInstance()->getGuards();
		$gs = $sender->getGameSession()->getGuards();

		switch(strtolower(array_shift($args))){
			case "c":
			case "create":
			case "new":
				if($gs->inPathMode()){
					$sender->sendMessage(TextFormat::RI . "You are already in path create mode!");
					return;
				}

				if(count($args) == 0){
					$sender->sendMessage(TextFormat::RI . "You must enter a path name!");
					return;
				}
				$name = strtolower(array_shift($args));
				$loops = (bool) array_shift($args) ?? false;

				$gs->setPathMode();
				$gs->setPath(new Path($name, [], $loops));
				$sender->sendMessage(TextFormat::GI . "You are now in path create mode! To set path points, stand somewhere and type " . TextFormat::YELLOW . "/gpath set" . TextFormat::GRAY . " or tap a target block! Type " . TextFormat::YELLOW . "/gp done" . TextFormat::GRAY . " to finish!");
				break;

			case "s":
			case "set":
				if(!$gs->inPathMode()){
					$sender->sendMessage(TextFormat::RI . "You are not in path create mode!");
					return;
				}

				$gs->addPoint($sender->getPosition()->floor());
				$sender->sendMessage(TextFormat::GI . "Added path point at " . TextFormat::YELLOW . $sender->getPosition()->floor());
				break;

			case "d":
			case "done":
				if(!$gs->inPathMode()){
					$sender->sendMessage(TextFormat::RI . "You are not in path create mode!");
					return;
				}

				$path = $gs->getPath();
				$gs->reset();
				$sender->sendMessage(TextFormat::GI . "Path has been saved with the name " . $path->getName() . TextFormat::GRAY . "!");
				break;

			case "nvm":
				if(!$gs->inPathMode()){
					$sender->sendMessage(TextFormat::RI . "You are not in path create mode!");
					return;
				}
				$gs->reset(false);
				$sender->sendMessage(TextFormat::GI . "Path data discarded.");
				break;

			case "list":
				$paths = $gu->getPathManager()->getPaths();
				$pstr = "";
				foreach($paths as $path){
					$pstr .= TextFormat::GRAY . "- " . TextFormat::AQUA . $path->getName() . " " . TextFormat::YELLOW . "LOOPS=" . ($path->doesLoop() ? TextFormat::GREEN . "TRUE" : TextFormat::RED . "FALSE") . PHP_EOL;
				}
				$sender->sendMessage(TextFormat::GI . "Listing all guard paths (" . TextFormat::YELLOW . count($paths) . TextFormat::GRAY . ")" . PHP_EOL . $pstr);
				return;

			default:
				$sender->sendMessage(TextFormat::RI . "Usage: /gpath [create <name> [loops=false] or [/gpath <set:done:nvm>]");
				break;
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}