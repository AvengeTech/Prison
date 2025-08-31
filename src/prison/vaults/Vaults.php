<?php namespace prison\vaults;

use pocketmine\Server;

use prison\Prison;
use prison\PrisonPlayer;
use prison\vaults\{
	commands\VaultCommand,
	commands\VaultSpyCommand,
};

use core\Core;
use core\inbox\object\{
	InboxInstance,
	MessageInstance
};
use core\session\mysqli\data\{
	MySqlRequest,
	MySqlQuery
};

class Vaults{

	public function __construct(public Prison $plugin){
		$plugin->getServer()->getCommandMap()->register("vault", new VaultCommand($plugin, "vault", "Open your private vault menu!"));
		$plugin->getServer()->getCommandMap()->register("vaultspy", new VaultSpyCommand($plugin, "vaultspy", "Moderate vaults"));
		//$this->sendOldVaultDataToInboxes();
	}

	public function close() : void{ //might be unnecessary
		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			/** @var PrisonPlayer $player */
			if($player->hasGameSession()){
				$session = $player->getGameSession()->getVaults();
				foreach($session->getVaults() as $vault){
					if($vault->inventory !== null){
						$inventory = $vault->inventory;
						$vault->setItems($inventory->getContents());
					}
				}
			}
		}
	}

	public function sendOldVaultDataToInboxes() : void{
		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("vaultholders", new MySqlQuery("main", "SELECT xuid, vdata FROM vault_data")), function(MySqlRequest $request) : void{
			$rows = $request->getQuery()->getResult()->getRows();
			$xuids = [];
			foreach($rows as $row){
				if(!in_array($row["xuid"], $xuids)) $xuids[] = $row["xuid"];
			}
			Core::getInstance()->getUserPool()->useUsers($xuids, function(array $users) use($rows) : void{
				foreach($rows as $row){
					$user = $users[$row["xuid"]];
					$inbox = new InboxInstance($user, "here");

					$data = unserialize(zlib_decode($row["vdata"]));
					$vaults = [];
					foreach($data as $vault){
						$vaults[] = $v = new Vault(null, $vault);
						echo "Vault with: " . count($v->getInventory()->getContents()) . " items", PHP_EOL;
						$items = $v->getInventory()->getContents();
						if(count($items) > 0){
							$msg = new MessageInstance($inbox, MessageInstance::newId(), time(), 0, "Vault " . $v->getId() . " return", "Vaults have been rewritten, so all previous vault data has been returned to your inbox.", false);
							$msg->setItems($items);
							$inbox->addMessage($msg, true);
						}
					}
				}
			});
		});
	}

}