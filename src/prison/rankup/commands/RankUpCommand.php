<?php namespace prison\rankup\commands;

use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;

use core\utils\TextFormat;

class RankUpCommand extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["ru"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		/** @var PrisonPlayer $sender */
		$rankup = Prison::getInstance()->getRankUp();
		$pl = $sender;
		$override = false;
		if(!$sender instanceof Player){
			if(count($args) == 0){
				$sender->sendMessage(TextFormat::RI . "Usage: /rankup <player>");
				return;
			}
			$pl = Server::getInstance()->getPlayerByPrefix(array_shift($args));
		}else{
			if($sender->isTier3() && !count($args) == 0){
				if (array_shift($args) == "-f") $override = true;
				else $pl = Server::getInstance()->getPlayerByPrefix(array_shift($args));
			}
		}
		if(!$pl instanceof Player){
			$sender->sendMessage(TextFormat::RI . "Player not online!");
			return;
		}

		/** @var PrisonPlayer $pl */
		$s = $sender === $pl && !$override;
		$session = $pl->getGameSession()->getRankUp();
		if($session->getRank() == "free"){
			$sender->sendMessage(TextFormat::YN . ($s ? "You are" : "This player is") . " already free and cannot rank up anymore! If you'd like learn more about " . TextFormat::YELLOW . "prestiging" . TextFormat::GRAY . ", type " . TextFormat::AQUA . "/prestige");
			return;
		}
		if($s && !$session->canRankUp()){
			$rank = $session->getRank();
			$newrank = ++$rank;
			$sender->sendMessage(TextFormat::RN . "You don't have enough Techits to rank up! Price: " . TextFormat::AQUA . $rankup->getRankUpPrice($rank) . " Techits");
			return;
		}
		$session->rankup($s);
		if(!$s){
			$sender->sendMessage(TextFormat::GI . "Force ranked up " . TextFormat::YELLOW . $pl->getName());
		}
		$msg = TextFormat::GI . "You ranked up to rank " . TextFormat::LIGHT_PURPLE . strtoupper($session->getRank()) . TextFormat::GRAY . "!";
		if($session->getRank() != "free"){
			$msg .= " You now have access to the " . TextFormat::LIGHT_PURPLE . strtoupper($session->getRank()) . " mine";
		}
		$pl->sendMessage($msg);
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}