<?php namespace prison\entity;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Projectile;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\math\RayTraceResult;

use core\techie\TechieBot;

class GrabHook extends Projectile{

	public $width = 0.25;
	public $height = 0.25;

	protected $gravity = 0;
	protected $drag = 0.05;

	protected function getInitialDragMultiplier(): float
	{
		return $this->drag;
	}

	protected function getInitialGravity(): float
	{
		return $this->gravity;
	}

	public function __construct(Location $location, ?Entity $shootingEntity, ?CompoundTag $nbt = null){
		parent::__construct($location, $shootingEntity, $nbt);
		if($shootingEntity instanceof Player){
			$shootingEntity->setTargetEntity($this);
		}else{
			$this->flagForDespawn();
		}
	}

	public function canSaveWithChunk() : bool{
		return false;
	}

	public function getName() : string{
		return "GrabHook";
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->closed){
			return false;
		}
		if(!($player = $this->getOwningEntity()) instanceof Player){
			$this->flagForDespawn();
			return false;
		}
		if($player->getPosition()->distance($this->getPosition()) > 10){
			$this->flagForDespawn();
			return false;
		}

		return $this->isAlive();
	}

	public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void{
		$player = $this->getOwningEntity();
		if(!$player instanceof Player) return;
		if(
			$entityHit instanceof TechieBot ||
			$entityHit instanceof ArmorStand
		) return;

		parent::onHitEntity($entityHit, $hitResult);

		$this->drag($player, $entityHit);
	}

	public function drag(Player $to, Entity $from) : void{
		if (!$from instanceof Living) return;
		$t = $from->getPosition()->asVector3();
		$dv = $to->getPosition()->asVector3()->subtract($t->x, $t->y, $t->z)->normalize();
		$from->knockback($dv->x, $dv->z, 0.8);
	}

	public static function getNetworkTypeId(): string{
		return "minecraft:fishing_hook";
	}

	protected function getInitialSizeInfo(): EntitySizeInfo{
		return new EntitySizeInfo($this->height, $this->width);
	}
}