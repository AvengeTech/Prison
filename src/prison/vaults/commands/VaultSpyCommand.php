<?php namespace prison\vaults\commands;

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

class VaultSpyCommand extends Command{
	
	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.staff");
		$this->setAliases(["vs"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player){
			if(!$sender->isStaff()){
				$sender->sendMessage(TextFormat::RN . "This is a STAFF ONLY feature!");
				return;
			}

			if(count($args) != 2){
				$sender->sendMessage(TextFormat::RN . "Usage: /vaultspy <player> <id>");
				return;
			}

			$name = array_shift($args);
			$id = (int) array_shift($args);

			$useVault = function(?PrisonSession $session) use($sender, $id) : void{
				if(!$sender->isConnected()) return;
				if($session === null){
					$sender->sendMessage(TextFormat::RI . "Player session not found, please try again!");
					return;
				}
				$vaults = $session->getVaults();
				$vault = $vaults->getVault($id);
				if($vault == null){
					$sender->sendMessage(TextFormat::RN . "Vault ID invalid!");
					return;
				}
				$vault->open($sender);
			};

			$player = $this->plugin->getServer()->getPlayerExact($name);

			if($player instanceof Player){
				/** @var PrisonPlayer $player */
				$session = $player->getGameSession();
				$useVault($session);
			}else{
				Core::getInstance()->getUserPool()->useUser($name, function(User $user) use($sender, $useVault) : void{
					if(!$sender->isConnected()) return;
					if(!$user->valid()){
						$sender->sendMessage(TextFormat::RI . "Player never seen!");
						return;
					}
					Prison::getInstance()->getSessionManager()->useSession($user, function(PrisonSession $session) use($sender, $useVault) : void{
						if(!$sender->isConnected()) return;
						$useVault($session);
					});
				});
			}
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}