<?php namespace prison\enchantments\effects\tasks;

use pocketmine\world\particle\{
	MobSpawnParticle,
	SmokeParticle,
	LavaDripParticle
};

class LavaRainAnimationTask extends AnimationTask{

	public function onRun() : void{
		$timer = $this->getTimer();
		if($this->isNew()){
			$this->cloud();
			$this->rain();
		}elseif($this->isLastCall()){
			$this->cloud();
		}else{
			switch(true){
				case $timer % 4 == 0:
					$this->cloud();
					break;
				case $timer < 30:
					$this->rain();
					break;
			}
		}

		parent::onRun();
	}

	public function cloud() : void{
		$pos = $this->getDeathPos();
		$np = $pos->add(0.5, 2.4, 0.5);
		if(mt_rand(0, 1) == 1){
			$pos->getWorld()->addParticle($np, new MobSpawnParticle());
		}else{
			for($i = 0; $i <= 5; $i++)
			$pos->getWorld()->addParticle($np, new SmokeParticle());
		}
	}

	public function rain() : void{
		$pos = $this->getDeathPos();
		for($i = 0; $i < 3; $i++){
			$np = $pos->add(mt_rand(0, 10) / 10, 2.5, mt_rand(0, 10) / 10);
			$pos->getWorld()->addParticle($np, new LavaDripParticle());
		}
	}

}