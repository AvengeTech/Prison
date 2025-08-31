<?php namespace prison\mysteryboxes\commands;

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

class SeeKeys extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["keys"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		$seeKeys = function(PrisonSession $session) use($sender) : void{
			if($sender instanceof Player && !$sender->isConnected()) return;
			$colors = [
				"iron" => TextFormat::WHITE,
				"gold" => TextFormat::GOLD,
				"diamond" => TextFormat::AQUA,
				"emerald" => TextFormat::GREEN,
				"vote" => TextFormat::YELLOW,
				"divine" => TextFormat::RED
			];
			$keys = TextFormat::GI . ($session->getUser()->getGamertag() === $sender->getName() ? "Your" : $session->getUser()->getGamertag() . "'s") . " keys:" . PHP_EOL;
			foreach($session->getMysteryBoxes()->getAllKeys() as $type => $amount){
				$keys .= $colors[$type] . number_format($amount) . " " . ucfirst($type) . " keys" . PHP_EOL;
			}
			$sender->sendMessage(rtrim($keys));
		};
		
		if($sender instanceof Player){
			/** @var PrisonPlayer $sender */
			if(count($args) == 0 || !$sender->isStaff()){
				$seeKeys($sender->getGameSession(), true);
				return;
			}
		}

		if(count($args) < 1){
			$sender->sendMessage(TextFormat::RN . "Usage: /seekeys <player>");
			return;
		}
		
		$name = strtolower(array_shift($args));
		$player = Server::getInstance()->getPlayerByPrefix($name);
		if($player instanceof Player) $name = $player->getName();
		
		Core::getInstance()->getUserPool()->useUser($name, function(User $user) use($sender, $seeKeys) : void{
			if($sender instanceof Player && !$sender->isConnected()) return;
			if(!$user->valid()){
				$sender->sendMessage(TextFormat::RI . "Player never seen!");
				return;
			}
			Prison::getInstance()->getSessionManager()->useSession($user, function(PrisonSession $session) use($sender, $seeKeys) : void{
				$seeKeys($session);
			});
		});
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}