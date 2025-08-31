<?php namespace prison\enchantments\effects\tasks;

use pocketmine\world\Position;

class RipAnimationTask extends AnimationTask{

	const ORIENTATION_1 = 1;
	const ORIENTATION_2 = 2;

	public $killerYaw;

	public function __construct(Position $deathPos, int $killerYaw = 0, int $timer = -1, bool $new = true){
		parent::__construct($deathPos, 3, $timer, $new);
		$this->killerYaw = $killerYaw;
	}

	public function getYaw() : int{
		return $this->killerYaw;
	}

	public function getOrientation() : int{
		return 1; //TOOD;
	}

	public function onRun() : void{
		$timer = $this->getTimer();
		if($this->isNew()){
			echo "Animation Started...", PHP_EOL;
		}else{
			switch($timer){
				case 10:
					echo "Animation Variation - Ticks: ";
					break;
				default:
					echo "Normal Animation Run - Ticks: ";
					break;
			}
			echo $timer, PHP_EOL;
		}

		if($this->isLastCall()){
			echo "Last task fire!", PHP_EOL;
		}

		//parent::onRun();
	}

}