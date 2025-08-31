<?php namespace prison\enchantments\effects\entities;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\item\VanillaItems;
use pocketmine\world\{
	particle\ExplodeParticle,
	particle\SmokeParticle,
	sound\PopSound
};
use pocketmine\math\Vector3;
use pocketmine\event\entity\EntityDamageEvent;

use core\utils\PlaySound;

class ExplodingChicken extends Living{

	public $width = 0.6;
	public $length = 0.6;
	public $height = 0.6;

	const LIFESPAN = 60; //ticks

	public $deathposition;

	public $aliveTicks = 0;
	public $target = null;

	public $eggs = [];

	public function __construct(Location $location, Vector3 $deathposition){
		parent::__construct($location);
		$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "mob.chicken.hurt"));
		$this->deathposition = $deathposition;
		$this->teleport($deathposition);
		$this->setCanSaveWithChunk(false);
	}

	public function getName() : string{
		return "Exploding Chicken";
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		$this->aliveTicks++;

		if($this->aliveTicks > self::LIFESPAN && $this->isAlive()){ //buhbye
			for($i = 0; $i < 3; $i++)
				$this->getWorld()->addParticle($this->getPosition(), new ExplodeParticle());

			$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "mob.chicken.death"));
			foreach($this->eggs as $egg){
				if(!$egg->isFlaggedForDespawn() && !$this->closed) $egg->flagForDespawn();
			}
			$this->flagForDespawn();
		}else{
			$this->walk();
			if($this->aliveTicks % 5 == 0) $this->layEgg();
		}

		return $this->isAlive();
	}

	public function atTarget() : bool{
		return $this->getPosition()->distance($this->getTarget()) < 2;
	}

	public function hasTarget() : bool{
		return $this->getTarget() !== null;
	}

	public function getTarget() : ?Vector3{
		return $this->target;
	}

	public function findTarget() : void{
		$this->target = $this->getPosition()->add(mt_rand(-5, 5), 0, mt_rand(-5, 5));
	}

	public function walk() : void{
		if(!$this->hasTarget() || $this->atTarget()){
			$this->findTarget();
			return;
		}

		$x = $this->getTarget()->x - $this->getPosition()->x;
		$y = $this->getTarget()->y - $this->getPosition()->y;
		$z = $this->getTarget()->z - $this->getPosition()->z;

		$this->motion->x = $this->getSpeed() * 0.35 * ($x / (abs($x) + abs($z)));
		$this->motion->z = $this->getSpeed() * 0.35 * ($z / (abs($x) + abs($z)));
		$this->setRotation(rad2deg(atan2(-$x, $z)), 0);
	}

	public function getSpeed() : float{
		return 1.7;
	}

	public function attack(EntityDamageEvent $source) : void{
		$source->cancel();

		for($i = 0; $i < 3; $i++)
			$this->getWorld()->addParticle($this->getPosition(), new SmokeParticle());

		foreach($this->eggs as $egg){
			if(!$egg->isClosed()) $egg->flagForDespawn();
		}
		$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "mob.chicken.death"));
		$this->flagForDespawn();
	}

	public function layEgg() : void{
		$this->getWorld()->addSound($this->getPosition(), new PopSound());
		$this->eggs[] = $this->getWorld()->dropItem($this->getPosition(), VanillaItems::EGG(), null, 10000);
	}


	protected function getInitialSizeInfo(): EntitySizeInfo{
		return new EntitySizeInfo($this->height, $this->width);
	}

	public static function getNetworkTypeId(): string{
		return "minecraft:chicken";
	}
}