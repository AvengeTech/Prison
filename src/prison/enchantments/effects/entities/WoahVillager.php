<?php namespace prison\enchantments\effects\entities;

use pocketmine\entity\{
	EntitySizeInfo,
	Living,
	Location,
	object\ItemEntity
};
use pocketmine\item\VanillaItems;
use pocketmine\world\{
	sound\PopSound,
	sound\EndermanTeleportSound
};
use pocketmine\math\Vector3;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;

use core\utils\{
	PlaySound,
	GenericSound
};

class WoahVillager extends Living{

	const BABY = 40;

	public $width = 0.7;
	public $length = 0.7;
	public $height = 2.2;

	const LIFESPAN = 60; //ticks

	public $deathposition;

	public $aliveTicks = 0;
	public $babyTicks = 0;

	public $drops = [];

	public function __construct(Location $location, Vector3 $deathposition){
		parent::__construct($location);
		$this->deathposition = $dp = $deathposition->add(0, 0.5, 0);
		$this->motion->y = 0.4;
		$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "mob.villager.yes"));
		$this->setCanSaveWithChunk(false);
	}

	public function getName() : string{
		return "Woah Villager";
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		$this->aliveTicks++;

		$this->setRotation($this->getLocation()->yaw + 10, $this->getLocation()->pitch);

		if($this->onGround)
			$this->jump();

		if($this->aliveTicks > self::BABY){
			if($this->getLocation()->pitch >= -30){
				$this->setRotation($this->getLocation()->yaw, $this->getLocation()->pitch -5);
			}
			$this->babyTicks++;
			if($this->babyTicks > self::LIFESPAN){
				foreach($this->drops as $drop){
					if($drop instanceof ItemEntity && !$drop->isFlaggedForDespawn() && !$drop->isClosed()){
						$drop->flagForDespawn();
					}
				}
				$this->getWorld()->addSound($this->getPosition(), new EndermanTeleportSound());
				$this->flagForDespawn();
				return false;
			}else{
				if(mt_rand(0, 4) == 1){
					$item = VanillaItems::EMERALD();
					$nbt = $item->getNamedTag();
					$nbt->setInt("pickup", 0);
					$item->setNamedTag($nbt);
					$this->dropItem($item);
				}
			}
		}

		return $this->isAlive();
	}

	public function jump() : void{
		parent::jump();
		if(!$this->isFlaggedForDespawn()) $this->getWorld()->addSound($this->getPosition(), new PopSound());
	}

	public function kill() : void{
		parent::kill();
		foreach($this->drops as $drop){
			if($drop instanceof ItemEntity && !$drop->isFlaggedForDespawn() && !$drop->isClosed()){
				$drop->flagForDespawn();
			}
		}
	}

	public function attack(EntityDamageEvent $source) : void{
		$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "mob.villager.yes"));
		$source->cancel();
	}

	public function dropItem(Item $item) : void{
		$motion = $this->getDirectionVector()->multiply(0.2);
		$drop = $this->getWorld()->dropItem($this->getPosition()->add(0, 0.65, 0), $item, $motion, 10000);
		$drop->setCanSaveWithChunk(false);
		$this->drops[] = $drop;

		$this->getWorld()->addSound($this->getPosition(), new GenericSound($this->getPosition(), 79));
	}

	protected function getInitialSizeInfo(): EntitySizeInfo{
		return new EntitySizeInfo($this->height, $this->width);
	}

	public static function getNetworkTypeId(): string{
		return "minecraft:villager";
	}
}