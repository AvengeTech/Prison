<?php namespace prison\enchantments\effects\entities;

use pocketmine\color\Color;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\world\{
	particle\DustParticle
};
use pocketmine\math\Vector3;
use pocketmine\event\entity\EntityDamageEvent;

use core\utils\PlaySound;

class FlyingLlama extends Living{

	public $width = 0.6;
	public $length = 0.6;
	public $height = 0.6;

	const LIFESPAN = 60; //ticks

	public $deathposition;

	public $aliveTicks = 0;

	public function __construct(Location $location, Vector3 $deathposition){
		parent::__construct($location);
		$this->deathposition = $dp = $deathposition;
		$this->motion->y = 1.2;
		$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "mob.llama.angry"));
		$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "firework.launch"));
		$this->setCanSaveWithChunk(false);
	}

	public function getName() : string{
		return "Flying Llama";
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		$this->aliveTicks++;

		for($i = 0; $i <= 5; $i++){
			$this->getWorld()->addParticle($this->getPosition()->add(mt_rand(-10, 10) / 10, 0, mt_rand(-10, 10) / 10), new DustParticle(new Color(mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255))));
		}
		if($this->motion->y <= 0){
			$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "mob.llama.death"));
			$this->kill();
			return false;
		}

		return $this->isAlive();
	}

	public function attack(EntityDamageEvent $source) : void{
		$source->cancel();
	}

	protected function getInitialSizeInfo(): EntitySizeInfo{
		return new EntitySizeInfo($this->height, $this->width);
	}

	public static function getNetworkTypeId(): string{
		return "minecraft:llama";
	}
}