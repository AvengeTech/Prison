<?php namespace prison\rankup\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;

use core\utils\TextFormat;

class PrestigeCommand extends Command{

	const PRESTIGE_PRICE = 1500000;

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["pr"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		/** @var PrisonPlayer $sender */
		if(!$sender instanceof Player){
			$sender->sendMessage(TextFormat::RI . "no");
			return;
		}

		$rankup = Prison::getInstance()->getRankUp();
		$session = $sender->getGameSession()->getRankUp();

		$prestige = $session->getPrestige() + 1;

		if ($session->getRank() !== "free") {
			$sender->sendMessage(TextFormat::YN . "You cannot use this command unless you are rank FREE!");
			return;
		}

		$price = $prestige * self::PRESTIGE_PRICE;
		if($sender->getTechits() < $price){
			$sender->sendMessage(TextFormat::RI . "You need " . TextFormat::AQUA . number_format($price) . " techits " . TextFormat::GRAY . "to reach prestige " . TextFormat::YELLOW . $prestige . "!");
			return;
		}

		if(count($args) !== 1 || strtolower($args[0]) !== "confirm"){
			$sender->sendMessage(TextFormat::YN . "Are you sure you would like to prestige for " . TextFormat::AQUA . number_format($price) . " techits?" . TextFormat::GRAY . " Once you run " . TextFormat::AQUA . "/prestige confirm" . TextFormat::GRAY . ", you will go back to rank A, and reach prestige " . TextFormat::GREEN . TextFormat::BOLD . $prestige);
			return;
		}

		$sender->takeTechits($price);
		$session->prestige();
		$ksession = $sender->getGameSession()->getMysteryBoxes();
		$ksession->addKeys("divine", 2);
		$sender->sendMessage(TextFormat::GI . "You have reached prestige " . TextFormat::YELLOW . $prestige . "! " . TextFormat::GRAY . "Your mine level has reset and you were given " . TextFormat::YELLOW . "2" . TextFormat::RED . " Divine Keys!");
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}