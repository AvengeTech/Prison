<?php namespace prison\mysteryboxes\commands;

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

class AddKeys extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission(!Prison::getInstance()->isTestServer() ? "prison.tier3" : "prison.perm");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player){
			if($sender->getRank() != "owner" && !$sender->isTier3() && !Prison::getInstance()->isTestServer()){
				$sender->sendMessage(TextFormat::RN . "You do not have permission to use this command");
				return;
			}
		}

		if(count($args) != 3){
			$sender->sendMessage(TextFormat::RN . "Usage: /addkeys <player> <amount> <type>");
			return;
		}

		$name = strtolower(array_shift($args));
		$amount = (int) array_shift($args);
		$type = strtolower(array_shift($args));

		$player = $this->plugin->getServer()->getPlayerExact($name);
		if($player instanceof Player){
			$name = $player->getName();
		}

		if($amount <= 0 || $amount >= 100000000){
			$sender->sendMessage(TextFormat::RN . "Amount must be a number between 1 and 100,000,000");
			return;
		}

		if(!in_array($type, ["iron", "gold", "diamond", "emerald", "divine", "vote"])){
			$sender->sendMessage(TextFormat::RN . "Invalid key type!");
			return;
		}

		Core::getInstance()->getUserPool()->useUser($name, function(User $user) use($sender, $player, $type, $amount) : void{
			if($sender instanceof Player && !$sender->isConnected()) return;
			if(!$user->valid()){
				$sender->sendMessage(TextFormat::RI . "Player never seen!");
				return;
			}
			Prison::getInstance()->getSessionManager()->useSession($user, function(PrisonSession $session) use($sender, $player, $type, $amount) : void{
				if($sender instanceof Player && !$sender->isConnected()) return;
				$session->getMysteryBoxes()->addKeys($type, $amount);
				if($player instanceof Player && $player->isConnected()){
					$player->sendMessage(TextFormat::GI . "You have received " . TextFormat::YELLOW . "x" . $amount . " " . $type . " keys!");
				}else{
					$session->getMysteryBoxes()->saveAsync();
				}
				$sender->sendMessage(TextFormat::GN . "Successfully gave " . $amount . " " . $type . " keys to " . $session->getUser()->getGamertag() . "!");
			});
		});
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}