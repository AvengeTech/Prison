<?php 

namespace prison\enchantments\commands;

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

class SetEssence extends Command{
	
	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.tier3");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player){
			if(!$sender->isTier3()){
				$sender->sendMessage(TextFormat::RI . "You cannot use this command.");
				return;
			}
		}
		if(count($args) != 2){
			$sender->sendMessage(TextFormat::RI . "Usage: /setessence <player> <amount>");
			return;
		}

		$name = array_shift($args);
		$amount = (int) array_shift($args);

		if($amount < 0){
			$sender->sendMessage(TextFormat::RI . "Amount must be at least 0!");
			return;
		}

		$player = $this->plugin->getServer()->getPlayerByPrefix($name);
		if($player instanceof Player){
			$name = $player->getName();
		}

		Core::getInstance()->getUserPool()->useUser($name, function(User $user) use($sender, $amount) : void{
			if(!$user->valid()){
				$sender->sendMessage(TextFormat::RI . "Player never seen!");
				return;
			}
			Prison::getInstance()->getSessionManager()->useSession($user, function(PrisonSession $session) use($sender, $user, $amount) : void{
				$session->getEssence()->setEssence($amount);
				if(!$user->validPlayer()){
					$session->getEssence()->saveAsync();
				}else{
					$user->getPlayer()->sendMessage(TextFormat::GI . "You have earned " . TextFormat::DARK_AQUA . $amount . " Essence" . TextFormat::GRAY . "!");
				}
				$sender->sendMessage(TextFormat::GI . "Successfully gave " . TextFormat::YELLOW . $user->getGamertag() . TextFormat::DARK_AQUA . " " . $amount . " Essence" . TextFormat::GRAY."!");
			});
		});
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}