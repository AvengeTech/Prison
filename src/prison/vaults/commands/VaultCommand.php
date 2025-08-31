<?php namespace prison\vaults\commands;

use pocketmine\block\{
	ShulkerBox,
	VanillaBlocks
};
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\{
	ItemBlock
};
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;
use prison\vaults\uis\VaultSelectUi;

use core\utils\TextFormat;

class VaultCommand extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["v", "pv"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		if(!$sender instanceof Player) return;
		/** @var PrisonPlayer $sender */

		if($sender->isBattleSpectator()){
			$sender->sendMessage(TextFormat::RI . "You cannot use vaults while spectating a battle!");
			return;
		}

		if(count($args) == 0){
			$sender->showModal(new VaultSelectUi($sender));
			return;
		}

		$vault = array_shift($args);
		$action = (count($args) == 0 ? "open" : strtolower(array_shift($args)));

		$session = $sender->getGameSession()->getVaults();
		$vault = $session->getVault($vault);
		if($vault == null || !$vault->isPlayerAccessible()){
			$sender->sendMessage(TextFormat::RN . "Invalid vault ID or name");
			return;
		}

		switch($action){
			case "dumphand":
				//$sender->sendMessage(TextFormat::RI . "Vaults are temporarily disabled while we investigate a rollback issue. To take items from your vault for now, type " . TextFormat::YELLOW . "/v <id> takeall");
				//return;
				if(!$vault->getInventory()->canAddItem($item = $sender->getInventory()->getItemInHand())){
					$sender->sendMessage(TextFormat::RN . "This vault is full!");
					return;
				}
				if(!(
					$item instanceof ItemBlock &&
					$item->getBlock() instanceof ShulkerBox
				)){
					$sender->sendMessage(TextFormat::RN . "You cannot put shulker boxes in your vault!");
					return;
				}
				$vault->getInventory()->addItem($sender->getInventory()->getItemInHand());
				$sender->getInventory()->setItemInHand(VanillaBlocks::AIR()->asItem());
				$session->setChanged();
				break;
			case "dumpinv":
			case "dumpall":
			case "dump":
				//$sender->sendMessage(TextFormat::RI . "Vaults are temporarily disabled while we investigate a rollback issue. To take items from your vault for now, type " . TextFormat::YELLOW . "/v <id> takeall");
				//return;
				$count = 0;
				foreach($sender->getInventory()->getContents() as $item){
					if($vault->getInventory()->canAddItem($item) && (!(
						$item instanceof ItemBlock &&
						$item->getBlock() instanceof ShulkerBox
					))){
						$count += $item->getCount();
						$vault->getInventory()->addItem($item);
						$sender->getInventory()->removeItem($item);
					}
				}
				if($count > 0){
					$sender->sendMessage(TextFormat::GN . "Dumped " . TextFormat::WHITE . $count . TextFormat::GRAY . " items into this vault!");
					$session->setChanged();
				}else{
					$sender->sendMessage(TextFormat::RN . "Either your vault is full or your inventory is empty!");
					return;
				}
				break;
			case "take":
			case "takeall":
			case "empty":
				//$sender->sendMessage(TextFormat::RI . "Vaults are temporarily disabled while we investigate a rollback issue. To take items from your vault for now, type " . TextFormat::YELLOW . "/v <id> takeall");
				//return;
				$count = 0;
				foreach($vault->getInventory()->getContents() as $item){
					if($sender->getInventory()->canAddItem($item)){
						$count += $item->getCount();
						$sender->getInventory()->addItem($item);
						$vault->getInventory()->removeItem($item);
					}
				}
				if($count > 0){
					$sender->sendMessage(TextFormat::GN . "Took " . TextFormat::WHITE . $count . TextFormat::GRAY . " items from this vault!");
					$session->setChanged();
				}else{
					$sender->sendMessage(TextFormat::RN . "Either your inventory is full or your vault is empty!");
					return;
				}
				break;
			default:
			case "open":
				$vault->open($sender);
				break;
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}