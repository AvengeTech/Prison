<?php namespace prison\enchantments\effects\tasks;

use pocketmine\math\Vector3;
use pocketmine\world\{
	particle\FlameParticle,
	particle\SmokeParticle,
	sound\GhastShootSound
};

class BurnAnimationTask extends AnimationTask{

	const PARTICLE_DENSITY = 50;
	const PARTICLE_RADIUS = 1.5;

	private static function getRandomVector() : Vector3{
		$x = 0; $y = 0; $z = 0;
		$x = rand() / getrandmax() * 2 - 1;
		$y = rand() / getrandmax() * 2 - 1;
		$z = rand() / getrandmax() * 2 - 1;
		$v = new Vector3($x, $y, $z);
		return $v->normalize();
	}


	public function onRun() : void{
		$timer = $this->getTimer();
		$pos = $this->getDeathPos();

		if($this->isNew()){
			$pos->getWorld()->addSound($pos, new GhastShootSound());
		}else{
			for($i = 0; $i < self::PARTICLE_DENSITY; ++$i){
				$vector = self::getRandomVector()->multiply(self::PARTICLE_RADIUS);
				if(mt_rand(0, 1) == 1){
					$pos->getWorld()->addParticle($pos->add($vector->x, $vector->y, $vector->z), new FlameParticle());
				}else{
					$pos->getWorld()->addParticle($pos->add($vector->x, $vector->y, $vector->z), new SmokeParticle());
				}
				$this->deathPos->add($vector->x, $vector->y, $vector->z);
			}
		}

		if($this->isLastCall()){

		}

		parent::onRun();
	}

}