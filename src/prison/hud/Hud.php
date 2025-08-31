<?php namespace prison\hud;

use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

use prison\Prison;
use prison\PrisonPlayer;

class Hud{

	private $huds = [];

	public function __construct(
		private Prison $plugin
	){}

	/**
	 * @return HudObject[]
	 */
	public function getHuds() : array{
		return $this->huds;
	}

	public function getHud(Player $player) : ?HudObject{
		return $this->huds[$player->getName()] ?? null;
	}

	public function send(Player $player) : void{
		/** @var PrisonPlayer $player */
		$hud = $this->huds[$player->getName()] = new HudObject($player);
		if(!$player->isFromProxy()){
			$hud->send();
		}else{
			Prison::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($hud) : void{
				$hud->send();
			}), 20);
		}
	}

	public function tick() : void{
		foreach($this->huds as $name => $hud){
			$player = $this->plugin->getServer()->getPlayerExact($name);
			if($player instanceof Player){
				$hud->update();
			}else{
				unset($this->huds[$name]);
			}
		}
	}

}