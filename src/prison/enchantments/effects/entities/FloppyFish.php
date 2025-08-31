<?php namespace prison\enchantments\effects\entities;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\world\{
	particle\SplashParticle
};
use pocketmine\math\Vector3;
use pocketmine\event\entity\EntityDamageEvent;

use core\utils\PlaySound;

class FloppyFish extends Living{

	public $width = 0.6;
	public $length = 0.6;
	public $height = 0.6;

	const LIFESPAN = 60; //ticks

	public $deathposition;

	public $aliveTicks = 0;

	public function __construct(Location $location, Vector3 $deathposition){
		parent::__construct($location);

		$this->deathposition = $dp = $deathposition;

		$this->teleport($deathposition);
		$this->setCanSaveWithChunk(false);
	}

	public function getName() : string{
		return "Floppy Fish";
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		$this->aliveTicks++;

		if($this->aliveTicks > self::LIFESPAN && $this->isAlive()){ //buhbye
			for($i = 0; $i < 3; $i++)
				$this->getWorld()->addParticle($this->getPosition(), new SplashParticle());

			$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "mob.fish.flop"));
			$this->flagForDespawn();
		}else{ //jumping intensifies
			$this->jump();
		}

		return $this->isAlive();
	}

	public function jump() : void{
		if($this->onGround){
			for($i = 0; $i < mt_rand(15, 20); $i++){
				$this->getWorld()->addParticle($this->getPosition()->add(mt_rand(-10, 10) / 10, 0, mt_rand(-10, 10) / 10), new SplashParticle());
			}
			$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "mob.fish.flop"));
			$this->motion->y = $this->getJumpVelocity(); //Y motion should already be 0 if we're jumping from the ground.
		}
	}

	public function attack(EntityDamageEvent $source) : void{
		$source->cancel();

		for($i = 0; $i < 3; $i++)
			$this->getWorld()->addParticle($this->getPosition(), new SplashParticle());

		$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "mob.fish.flop"));
		$this->flagForDespawn();
	}

	protected function getInitialSizeInfo(): EntitySizeInfo{
		return new EntitySizeInfo($this->height, $this->width);
	}

	public static function getNetworkTypeId(): string{
		return "minecraft:tropicalfish";
	}
}