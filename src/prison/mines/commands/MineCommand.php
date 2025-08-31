<?php namespace prison\mines\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;
use prison\mines\PrestigeMine;
use prison\mines\ui\{
	MinesUi,
	MineListUi
};

use core\Core;
use core\utils\ItemRegistry;
use core\utils\TextFormat;
use core\network\protocol\PlayerLoadActionPacket;

class MineCommand extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["mines"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		/** @var PrisonPlayer $sender */
		if(!$sender instanceof Player && (count($args) !== 2 || strtolower($args[1] ?? "") != "reset")){
			$sender->sendMessage("no");
			return;
		}
		if($sender instanceof Player && $sender->isBattleParticipant()){
			$sender->sendMessage(TextFormat::RI . "You cannot use this command while in a gang battle!");
			return;
		}
		if ($commandLabel === "mines") {
			$sender->showModal(new MinesUi($sender));
			return;
		}
		if (count($args) == 0) {
			$rank = ($rankup = $sender->getGameSession()->getRankUp())->getRank();
			$prestige = min(25, $rankup->getPrestige() == 0 ? 0 : ($rankup->getPrestige() == 1 ? 1 : (max(1, floor($rankup->getPrestige() / 5) * 5))));
			if ($rank == "free") $rank = "z";
			if ($prestige > 0) {
				$args[] = "p" . $prestige;
			} else {
				$args[] = $rank;
			}
		}
		

		$mines = Prison::getInstance()->getMines();
		$mine = strtolower(array_shift($args));

		if($sender instanceof Player){
			$session = $sender->getGameSession()->getMines();

			if(!$mines->mineExists($mine)){
				switch($mine){
					case "prestige":
						if($sender->getPrestige() < 1){
							$sender->sendMessage(TextFormat::RI . "You must prestige at least once to access the prestige mines!");
							return;
						}
						$ms = $session->getUnlockedMines();
						foreach($ms as $key => $name){
							$m = $mines->getMineByName($name);
							if(!$m instanceof PrestigeMine){
								unset($ms[$key]);
							}else{
								$ms[$key] = $m;
							}
						}
						$sender->showModal(new MineListUi($sender, $ms));
						return;
				}
				$sender->sendMessage(TextFormat::RN . "This mine doesn't exist!");
				return;
			}


			if(!$session->canTeleportTo($mine)){
				if($sender->getRankHierarchy() < 8){
					$sender->sendMessage(TextFormat::RN . "You don't have this mine unlocked!");
					return;
				}
			}

			if($mine == "vip" && Core::getInstance()->getNetwork()->getThisServer()->getTypeId() == "event"){
				$sender->sendMessage(TextFormat::RI . "VIP mine is disabled during events!");
				return;
			}
		}

		$am = $mines->getMineByName($mine);
		if($am !== null && $am->pvp() && ($sender instanceof Player && $sender->getArmorInventory()->getItem(1)->getTypeId() == ItemRegistry::ELYTRA()->getTypeId())){
			$sender->sendMessage(TextFormat::RI . "Please take your Elytra off before entering the PvP mine!");
			return;
		}

		if(!empty($args) && (!$sender instanceof Player || ($sender->isStaff() || $sender->getRank() == "enderdragon")) && array_shift($args) == "reset"){
			if($sender instanceof Player && $sender->getRank() == "enderdragon"){
				if(!$session->canResetAgain()){
					$sender->sendMessage(TextFormat::RI . "You have recently reset a mine! You can reset another one in " . TextFormat::YELLOW . $session->getTimeLeftToReset() . " seconds");
					return;
				}
				if(!$am->canResetAgain()){
					$sender->sendMessage(TextFormat::RI . "This mine has recently been reset! It can be reset again in " . TextFormat::YELLOW . $am->getTimeLeftToReset() . " seconds");
					return;
				}
				$session->setLastReset();
			}

			$am->reset();
			$sender->sendMessage(TextFormat::GI . "Resetting mine " . TextFormat::LIGHT_PURPLE . strtoupper($mine));
			return;
		}
		if($am->pvp()){
			if(!($ts = Core::thisServer())->isSubServer() || $ts->getSubId() !== "pvp"){
				echo "transfer?", PHP_EOL;
				(new PlayerLoadActionPacket([
					"player" => $sender->getName(),
					"server" => "prison-" . $ts->getTypeId() . "-pvp",
					"action" => "mine",
					"actionData" => ["id" => "pvp"]
				]))->queue();
				$sender->gotoPvPserver(TextFormat::GI . "Teleported to mine " . TextFormat::LIGHT_PURPLE . strtoupper($mine));
			}else{
				echo "enter?", PHP_EOL;
				$session->enterMine($mine, $sender->getRankHierarchy());
				$sender->sendMessage(TextFormat::GI . "Teleported to mine " . TextFormat::LIGHT_PURPLE . strtoupper($mine));		
			}
		}else{
			if(!($ts = Core::thisServer())->isSubServer()){
				$session->enterMine($mine, $sender->getRankHierarchy());
				$sender->sendMessage(TextFormat::GI . "Teleported to mine " . TextFormat::LIGHT_PURPLE . strtoupper($mine));		
			}else{
				(new PlayerLoadActionPacket([
					"player" => $sender->getName(),
					"server" => "prison-" . $ts->getTypeId(),
					"action" => "mine",
					"actionData" => ["id" => $mine]
				]))->queue();
				$sender->gotoSpawn(false, TextFormat::GI . "Teleported to mine " . TextFormat::LIGHT_PURPLE . strtoupper($mine));
			}
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}