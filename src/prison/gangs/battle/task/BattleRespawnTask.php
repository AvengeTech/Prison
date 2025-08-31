<?php namespace prison\gangs\battle\task;

use pocketmine\{
	player\Player,
	Server
};
use pocketmine\scheduler\Task;

use prison\Prison;
use prison\gangs\battle\Battle;

use core\utils\TextFormat;

class BattleRespawnTask extends Task{

	const RESPAWN_COUNTDOWN = 3;

	public $player;
	public $battle;

	public $runs = 0;

	public function __construct(Player $player, Battle $battle, int $runs = 0){
		$this->player = $player->getName();
		$this->battle = $battle;

		$this->runs = $runs;
	}

	public function getPlayer() : ?Player{
		return Server::getInstance()->getPlayerExact($this->player);
	}

	public function getBattle() : ?Battle{
		return Prison::getInstance()->getGangs()->getGangManager()->getBattleManager()->getBattleByBattle($this->battle);
	}

	public function onRun() : void{
		$this->runs++;
		$player = $this->getPlayer();
		$battle = $this->getBattle();
		if(
			$player instanceof Player && $battle instanceof Battle &&
			$battle->isParticipating($player)
		){
			if(($in = self::RESPAWN_COUNTDOWN - $this->runs) <= 0){
				$battle->respawn($player);
				$player->sendTitle(TextFormat::DARK_GREEN . "Respawned!", TextFormat::GREEN . "Go fight!", 5, 20, 5);
			}else{
				$player->sendTitle(TextFormat::RED . "Respawning in...", TextFormat::YELLOW . $in, 5, 20, 5);
				Prison::getInstance()->getScheduler()->scheduleDelayedTask(new BattleRespawnTask($player, $battle, $this->runs), 20);
			}
		}else{
			echo "no work", PHP_EOL;
		}
	}

}