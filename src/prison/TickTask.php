<?php namespace prison;

use pocketmine\player\Player;
use pocketmine\scheduler\Task;

use core\Core;
use core\utils\TextFormat;

class TickTask extends Task{

	public int $ktimer = 0;
	public int $runs = 0;

	public function __construct(public Prison $plugin){
		$this->resetKothTimer();
	}

	public function resetKothTimer() : void{
		if(Core::thisServer()->isSubServer()){
			$this->ktimer = PHP_INT_MAX;
			return;
		}
		$this->ktimer = time() + (60 * 30);
	}

	public function onRun() : void{
		$this->runs++;
		$currentTick = $this->runs;

		if($currentTick %12000 == 0){
			$this->plugin->saveAll();
		}
		if($currentTick %5 == 0){
			$this->plugin->getSessionManager()?->tick();
		}
		if($currentTick %20 == 0){
			if(!Core::thisServer()->isSubServer()){
				$this->plugin->getAuctionHouse()->tick();
			}
			$this->plugin->getBlockTournament()->tick();
			$this->plugin->getCells()->tick();
			$this->plugin->getKoth()->tick();
			$this->plugin->getLeaderboards()->tick();
			$this->plugin->getGangs()->tick();
			$this->plugin->getGrinder()->tick();
			$this->plugin->getHud()->tick();
			$this->plugin->getTrash()->tick();
			$this->plugin->getQuests()->tick();

			if(time() > $this->ktimer){
				$koth = $this->plugin->getKoth();
				if(count($koth->getActiveGames()) === 0){
					$koth->startKoth();
					$this->resetKothTimer();
				}
			}

			foreach($this->plugin->getEnchantments()->nukecd as $name => $data){
				if($this->plugin->getEnchantments()->nukecd[$name] - time() <= 0){
					unset($this->plugin->getEnchantments()->nukecd[$name]);
					$player = $this->plugin->getServer()->getPlayerExact($name);
					if($player instanceof Player){
						$player->sendMessage(TextFormat::YN . "You may now throw another Mine Nuke!");
					}
				}
			}
			foreach($this->plugin->getEnchantments()->gncd as $name => $data){
				if($this->plugin->getEnchantments()->gncd[$name]["time"] - time() <= 0){
					unset($this->plugin->getEnchantments()->gncd[$name]);
					$player = $this->plugin->getServer()->getPlayerExact($name);
					if($player instanceof Player){
						$player->sendMessage(TextFormat::YN . "You may now throw more Mine Grenades!");
					}
				}
			}
			foreach($this->plugin->getEnchantments()->hbcd as $name => $data){
				if($this->plugin->getEnchantments()->hbcd[$name] - time() <= 0){
					unset($this->plugin->getEnchantments()->hbcd[$name]);
					$player = $this->plugin->getServer()->getPlayerExact($name);
					if($player instanceof Player){
						$player->sendMessage(TextFormat::YN . "You may now throw another Haste Bomb!");
					}
				}
			}
		}

		if($currentTick %5 == 0){
			$this->plugin->getEnchantments()->tick($currentTick);
		}

		foreach($this->plugin->getMysteryBoxes()->getBoxes() as $id => $box){
			$box->tick();
		}
	}

}