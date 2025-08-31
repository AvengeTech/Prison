<?php namespace prison\rankup\commands;

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

class PlaytimeCommand extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["pt"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		/** @var PrisonPlayer $sender */
		if(!$sender instanceof Player && count($args) == 0){
			$sender->sendMessage(TextFormat::RI . "Usage: /playtime <player>");
			return;
		}

		$rankup = Prison::getInstance()->getRankUp();
		if($sender instanceof Player && count($args) > 0){
			if(!$sender->isStaff() && $rankup->hasCooldown($sender)){
				$sender->sendMessage(TextFormat::RI . "You must wait another " . TextFormat::YELLOW . $rankup->getCooldown($sender) . " seconds" . TextFormat::GRAY . " before searching another player's playtime!");
				return;
			}
		}

		$search = function(PrisonSession $session, bool $other) use($sender) : void{
			if($sender instanceof Player && !$sender->isConnected()) return;
			$time = $session->getRankUp()->getFormattedPlaytime(!$other);
			$sender->sendMessage(TextFormat::YI . ($other ? $session->getUser()->getGamertag() . "'s" : "Your") . " playtime: " . TextFormat::WHITE . $time);
		};
		$other = false;
		if(count($args) == 0){
			if(!$sender instanceof Player){
				$sender->sendMessage(TextFormat::RI . "Beans boi");
				return;
			}
			$session = $sender->getGameSession();
			$search($session, $other);
		}else{
			$other = true;
			$name = array_shift($args);
			$player = Server::getInstance()->getPlayerByPrefix($name);
			if($player instanceof Player) $name = $player->getName();
			Core::getInstance()->getUserPool()->useUser($name, function(User $user) use($sender, $search) : void{
				if($sender instanceof Player && !$sender->isConnected()) return;
				if(!$user->valid()){
					$sender->sendMessage(TextFormat::RI . "Player never seen!");
					return;
				}
				Prison::getInstance()->getSessionManager()->useSession($user, function(PrisonSession $session) use($search) : void{
					$search($session, true);
				});
			});
		}
		if($sender instanceof Player) $rankup->setCooldown($sender);
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}