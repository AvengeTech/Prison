<?php namespace prison\enchantments\effects\entities;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\world\{
	Position,
	particle\SmokeParticle,
	particle\ExplodeParticle,
	sound\PopSound
};
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\math\Vector3;
use pocketmine\event\entity\EntityDamageEvent;

use core\utils\PlaySound;

class DreamBoat extends Entity{

	public $width = 0.6;
	public $length = 0.6;
	public $height = 0.6;

	const LIFESPAN = 15; //ticks

	public $deathposition;

	public $aliveTicks = 0;

	protected function getInitialDragMultiplier(): float
	{
		return 0;
	}

	protected function getInitialGravity(): float
	{
		return 0;
	}

	public function __construct(Location $loc, Vector3 $deathposition){
		parent::__construct($loc);
		$this->getWorld()->addSound($this->getPosition(), new PopSound());
		$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "player.hurt"));
		$this->deathposition = $deathposition;
		$this->teleport($deathposition);
		$this->setCanSaveWithChunk(false);
	}

	public function getName() : string{
		return "Dream Boat";
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		$this->aliveTicks++;

		if($this->aliveTicks > self::LIFESPAN && $this->isAlive()){ //buhbye
			for($i = 0; $i < 3; $i++)
				$this->getWorld()->addParticle($this->getPosition(), new ExplodeParticle());

			$this->getWorld()->addSound($this->getPosition(), new PopSound());

			$this->flagForDespawn();
		}

		return $this->isAlive();
	}

	public function attack(EntityDamageEvent $source) : void{
		$source->cancel();

		for($i = 0; $i < 3; $i++)
			$this->getWorld()->addParticle($this->getPosition(), new SmokeParticle());

		$this->getWorld()->addSound($this->getPosition(), new PopSound());

		$this->flagForDespawn();
	}

	protected function getInitialSizeInfo(): EntitySizeInfo{
		return new EntitySizeInfo($this->height, $this->width);
	}

	public static function getNetworkTypeId(): string{
		return "minecraft:boat";
	}
}