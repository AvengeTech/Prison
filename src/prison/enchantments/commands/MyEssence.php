<?php
namespace prison\enchantments\commands;

use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;
use prison\PrisonSession;

use core\Core;
use core\user\User;
use core\utils\TextFormat;

class MyEssence extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["essence"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player){
			if(count($args) == 0 || !$sender->isStaff()){
				$sender->sendMessage(TextFormat::YN . "You have " . TextFormat::DARK_AQUA . number_format($sender->getGameSession()->getEssence()->getEssence()) . " Essence");
				return;
			}
		}

		if(count($args) == 0){
			$sender->sendMessage(TextFormat::RI . "Please enter a username!");
			return;
		}

		$name = array_shift($args);
		$player = Server::getInstance()->getPlayerByPrefix($name);
		if($player instanceof Player){
			$name = $player->getName();
		}

		Core::getInstance()->getUserPool()->useUser($name, function(User $user) use($sender) : void{
			if(!$user->valid()){
				if($sender->isConnected()) $sender->sendMessage(TextFormat::RI . "Player never seen!");
				return;
			}
			Prison::getInstance()->getSessionManager()->useSession($user, function(PrisonSession $session) use($sender, $user) : void{
				if($sender->isConnected()) $sender->sendMessage(TextFormat::YN . "Player " . TextFormat::YELLOW . $user->getGamertag() . TextFormat::GRAY . " has " . TextFormat::DARK_AQUA . number_format($session->getEssence()->getEssence()) . " essence");
			});
		});
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}