<?php namespace prison\rankup\commands;

use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;

use core\utils\TextFormat;
use prison\rankup\RankUp;

class RankUpMaxCommand extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["rumax"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		/** @var PrisonPlayer $sender */
		$player = $sender;
		if(!($sender instanceof Player)){
			if(!(isset($args[0]))){
				$sender->sendMessage(TextFormat::RI . 'Usage: /rankupmax <string: player>');
				return;
			}

			$player = Server::getInstance()->getPlayerByPrefix($args[0]);
		}elseif($sender->isTier3() && isset($args[1])){
			$player = Server::getInstance()->getPlayerByPrefix($args[1]);
		}

		if(!($player instanceof PrisonPlayer)){
			$sender->sendMessage(TextFormat::RI . 'Player is not online!');
			return;
		}

		$rankup = Prison::getInstance()->getRankUp();
		$targetIsSender = ($sender->getXuid() === $player->getXuid());
		$session = $player->getGameSession()->getRankUp();
		$originalRank = $session->getRank();

		if($session->getRank() == "free"){
			$sender->sendMessage(TextFormat::YN . ($targetIsSender ? "You are" : "This player is") . " already free and cannot rank up anymore! If you'd like learn more about " . TextFormat::YELLOW . "prestiging" . TextFormat::GRAY . ", type " . TextFormat::AQUA . "/prestige");
			return;
		}

		if(!($targetIsSender)){
			$session->rankupMax('free', false);
			$sender->sendMessage(TextFormat::GI . "Force ranked up " . TextFormat::YELLOW . $player->getName());
		}else{
			if(empty($args) || isset($args[0]) && strtolower($args[0]) !== 'confirm'){
				$sender->sendMessage(TextFormat::RI . 'Usage: /rankupmax confirm');
				return;
			}

			$newrank = $rankup->getNextRank($session->getRank());

			while($session->canRankUp($newrank) && $rankup->getNextRank($newrank) != -1){
				$newrank = $rankup->getNextRank($newrank);
			}

			if($newrank == $session->getRank() || !$session->canRankUp()){
				$sender->sendMessage(TextFormat::RN . "You don't have enough Techits to use rank up max! Next Rank Price: " . TextFormat::AQUA . $rankup->getRankUpPrice($newrank) . " Techits");
				return;
			}

			$session->rankupMax($newrank);
		}

		$msg = TextFormat::GI . "You ranked up from rank " . TextFormat::AQUA . strtoupper($originalRank) . TextFormat::GRAY . ' to ' . TextFormat::GOLD . strtoupper($session->getRank()) . TextFormat::GRAY . "!";

		if($session->getRank() != "free"){
			$msg .= " You now have access to the " . TextFormat::LIGHT_PURPLE . strtoupper($session->getRank()) . " mine";
		}

		$player->sendMessage($msg);
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}