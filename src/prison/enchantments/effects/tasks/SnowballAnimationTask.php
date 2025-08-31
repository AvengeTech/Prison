<?php namespace prison\enchantments\effects\tasks;

use pocketmine\entity\Location;
use pocketmine\math\Vector3;

use prison\enchantments\effects\entities\PhaseSnowball;

class SnowballAnimationTask extends AnimationTask{

	public function onRun() : void{
		$timer = $this->getTimer();
		if($this->isNew()){
			for($i = 0; $i <= 3; $i++)
				$this->snowball();
		}elseif($this->isLastCall()){
			for($i = 0; $i <= 4; $i++)
				$this->snowball();
		}else{
			if($timer % 3 == 0)
				$this->snowball();
		}

		parent::onRun();
	}

	public function snowball() : void{
		$pos = $this->getDeathPos();
		$yaw = mt_rand(0, 360);
		$pitch = 0;

		$motX = -sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI);
		$motY = -sin($pitch / 180 * M_PI);
		$motZ = cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI);
		$motV = (new Vector3($motX, $motY, $motZ))->multiply(0.75);

		$entity = new PhaseSnowball(Location::fromObject($pos, $pos->getWorld()), null);
		$entity->setMotion($motV);
		$entity->spawnToAll();
	}

}