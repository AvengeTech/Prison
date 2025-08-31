<?php namespace prison\enchantments\effects\tasks;

use pocketmine\scheduler\Task;
use pocketmine\world\Position;

use prison\Prison;

class AnimationTask extends Task{

	public $deathPos;
	public $timer;

	public $new = true;

	public function __construct(Position $deathPos, int $seconds = 3, int $timer = -1, bool $new = true){
		$this->deathPos = $deathPos->asPosition();
		$this->timer = ($timer == -1 ? $seconds * 20 : $timer);
		$this->new = $new;
	}

	public function getDeathPos() : Position{
		return $this->deathPos;
	}

	public function getTimer() : int{
		return $this->timer;
	}

	public function isNew() : bool{
		return $this->new;
	}

	public function isLastCall() : bool{
		return $this->timer - 1 <= 0;
	}

	public function onRun() : void{
		$this->timer--;
		if($this->timer > 0){
			Prison::getInstance()->getScheduler()->scheduleDelayedTask(new $this($this->getDeathPos(), 0, $this->getTimer(), false), 1);
		}
	}

}