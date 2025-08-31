<?php namespace prison\enchantments\effects\entities;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\world\{
	particle\SmokeParticle
};
use pocketmine\math\Vector3;
use pocketmine\event\entity\EntityDamageEvent;

use core\utils\PlaySound;

class WitchcraftBat extends Living{

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
		$this->teleport($dp->add(mt_rand(-10, 10) / 10, mt_rand(0, 20) / 20, mt_rand(-10, 10) / 10));
		$this->deathposition = $deathposition->subtract(0, 1.5, 0);

		$this->setCanSaveWithChunk(false);
	}

	public function applyGravity() : void{}

	public function getName() : string{
		return "Witchcraft Bat";
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		$this->aliveTicks++;

		if($this->aliveTicks > self::LIFESPAN && $this->isAlive()){ //buhbye
			for($i = 0; $i < 3; $i++)
				$this->getWorld()->addParticle($this->getPosition(), new SmokeParticle());

			$this->flagForDespawn();
		}else{ //flying intensifies
			$this->lookAt($this->deathposition);

			$dir = $this->getPosition()->asVector3()->subtract($this->deathposition->x, $this->deathposition->y, $this->deathposition->z)->normalize()->multiply(0.05);
			$this->move($dir->getX(), $dir->getY(), $dir->getZ());
		}

		return $this->isAlive();
	}

	public function attack(EntityDamageEvent $source) : void{
		$source->cancel();

		for($i = 0; $i < 3; $i++)
			$this->getWorld()->addParticle($this->getPosition(), new SmokeParticle());

		$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "mob.bat.death"));
		$this->flagForDespawn();
	}

	protected function getInitialSizeInfo(): EntitySizeInfo{
		return new EntitySizeInfo($this->height, $this->width);
	}

	public static function getNetworkTypeId(): string{
		return "minecraft:bat";
	}

	protected function syncNetworkData(EntityMetadataCollection $properties): void{
		parent::syncNetworkData($properties);
		$properties->setGenericFlag(EntityMetadataFlags::AFFECTED_BY_GRAVITY, false);
	}
}