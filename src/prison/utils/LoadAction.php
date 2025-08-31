<?php namespace prison\utils;

use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\scheduler\ClosureTask;

use prison\{
	Prison,
	PrisonPlayer
};

use core\Core;
use core\utils\{
	LoadAction as CoreLoadAction,
	TextFormat
};

class LoadAction extends CoreLoadAction{

	public function process(bool $preLoad = false) : void{
		/** @var SkyBlockPlayer $player */
		$player = $this->getPlayer();
		$adata = $this->getActionData();
		if(!$player instanceof Player) return;
		switch($this->getAction()){
			case "mine":
				if(!$preLoad){
					//Core::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $adata): void {
						if ($player->isConnected()) Server::getInstance()->dispatchCommand($player, "mine " . $adata["id"]); //idc kys
					//}), 1);
				}
				break;

			case "plots":
				$plotType = $adata["type"];
				switch($plotType){
					case 0: //normal
						if(!Server::getInstance()->getWorldManager()->isWorldLoaded("s0plots")){
							Server::getInstance()->getWorldManager()->loadWorld("s0plots");
						}
						$player->teleport(new Position(32, 56, 32, Server::getInstance()->getWorldManager()->getWorldByName("s0plots")), 0, 0);
						break;
					case 1: //nether
						if(!Server::getInstance()->getWorldManager()->isWorldLoaded("nether_plots_s0")){
							Server::getInstance()->getWorldManager()->loadWorld("nether_plots_s0");
						}
						$player->changeDimensionTeleport(1, new Position(36.5, 56, 37.5, Server::getInstance()->getWorldManager()->getWorldByName("nether_plots_s0")), 0, 0);
						break;
					case 2: //end
						if(!Server::getInstance()->getWorldManager()->isWorldLoaded("end_plots_s0")){
							Server::getInstance()->getWorldManager()->loadWorld("end_plots_s0");
						}
						$player->changeDimensionTeleport(2, new Position(63.5, 57, 63.5, Server::getInstance()->getWorldManager()->getWorldByName("end_plots_s0")), 0, 0);
						break;
				}
				break;

			case "hangout":
				$player->teleport(new Position(-833.5, 24, 383.5, Server::getInstance()->getWorldManager()->getWorldByName("newpsn")), 90, 0);
				if(!$preLoad){
					$player->sendMessage(TextFormat::GN . "Teleported to hangout!");
				}
				break;
				
			case "grinder":
				$player->teleport(new Position(-777.5, 24, 383.5, Server::getInstance()->getWorldManager()->getWorldByName("newpsn")), 270, 0);
				if(!$preLoad){
					$player->sendMessage(TextFormat::GN . "Teleported to grinder!");
				}
				break;
				
			case "koth":
				$gameId = $adata["gameId"];
				$game = Prison::getInstance()->getKoth()->getGameById($gameId);
				if(!$game->isActive()){
					$player->sendMessage(TextFormat::RI . "The KOTH match you are trying to teleport to is no longer active.");
					$player->gotoSpawn();
					return;
				}

				if($preLoad){
					$game->teleportTo($player, false, true);
					return;
				}

				$game->teleportTo($player, true, false);
				break;

			case "lms":
				$gameId = $adata["gameId"];
				$game = Prison::getInstance()->getLms()->getGameById($gameId);
				if(!$game->isActive()){
					$player->sendMessage(TextFormat::RI . "The LMS match you are trying to teleport to is no longer active.");
					$player->gotoSpawn();
					return;
				}

				if($preLoad){
					$game->teleportTo($player, false, true);
					return;
				}

				$game->teleportTo($player, true, false);
				break;

			default:
				parent::process($preLoad);
				break;
		}
	}

}