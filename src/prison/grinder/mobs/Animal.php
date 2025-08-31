<?php namespace prison\grinder\mobs;

use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\event\entity\{
	EntityDamageEvent,
	EntityDamageByEntityEvent
};
use pocketmine\math\Vector3;

use core\utils\TextFormat;

use prison\PrisonPlayer;

abstract class Animal extends Living{

	const STACK_DISTANCE = 8;

	const IDLE_DISTANCE = 20;
	const IDLE_TIMEOUT = 1200;

	public $stackValue = 1;

	public $target = null;
	public $walkTime = -1;
	public $stayTime = 0;
	public $panicTime = 0;
	public $speed = 0.7;

	public $idleTime = 0;

	public function __construct(Location $location){
		parent::__construct($location);
		$this->setNametagVisible(true);
		$this->setNameTagAlwaysVisible(true);
		$this->setNametag(TextFormat::AQUA . TextFormat::BOLD . $this->getName() . TextFormat::YELLOW . " (" . $this->getStackValue() . ")" . TextFormat::RESET . TextFormat::GREEN . " " . $this->getHealth() . "/" . $this->getMaxHealth());
		$this->setRotation(mt_rand(0, 360), 0);
	}

	public function getName() : string{
		return "Animal";
	}

	public function getSpeed() : float{
		return $this->panicTime > 0 ? $this->speed * 2 : $this->speed;
	}

	public function getXpDrop() : int{
		return mt_rand(1, 3);
	}

	public function kill() : void{
		if($this->getStackValue() == 1){
			parent::kill();
			foreach($this->getDrops() as $item){
				$this->getWorld()->dropItem($this->getLocation(), $item);
			}
		}else{
			$this->doDeath();
			$this->subStackValue();
			$this->setHealth($this->getMaxHealth());
			$this->setNametag(TextFormat::AQUA . TextFormat::BOLD . $this->getName() . TextFormat::YELLOW . " (" . $this->getStackValue() . ")" . TextFormat::RESET . TextFormat::GREEN . " " . $this->getHealth() . "/" . $this->getMaxHealth());
		}
	}

	public function doDeath() : void{
		foreach($this->getDrops() as $item){
			$this->getWorld()->dropItem($this->getLocation(), $item);
		}
		$this->getWorld()->dropExperience($this->getPosition(), $this->getXpDrop());

		$ldc = $this->getLastDamageCause();
		if(!$ldc instanceof EntityDamageByEntityEvent) return;
		/** @var PrisonPlayer $player */
		$player = $ldc->getDamager();
		if(!$ldc->getDamager() instanceof Player) return;

		$csession = $player->getGameSession()->getCombat();
		$csession->addGrinderKill();

		$session = $player->getGameSession()->getMysteryBoxes();
		if(mt_rand(0,75) == 1) $session->addKeysWithPopup("iron", 1, "mob." . strtolower($this->getName()) . ".hurt");
		if(mt_rand(0,250) == 1) $session->addKeysWithPopup("gold", 1, "mob." . strtolower($this->getName()) . ".hurt");
		if(mt_rand(0,600) == 1) $session->addKeysWithPopup("diamond", 1, "mob." . strtolower($this->getName()) . ".hurt");
		if(mt_rand(0,750) == 1) $session->addKeysWithPopup("emerald", 1, "mob." . strtolower($this->getName()) . ".hurt");
	}

	public function attack(EntityDamageEvent $source) : void{
		parent::attack($source);
		if(!$source->isCancelled()){
			$this->setNametag(TextFormat::AQUA . TextFormat::BOLD . $this->getName() . TextFormat::YELLOW . " (" . $this->getStackValue() . ")" . TextFormat::RESET . TextFormat::GREEN . " " . $this->getHealth() . "/" . $this->getMaxHealth());
			if($source instanceof EntityDamageByEntityEvent){
				$this->panicTime = mt_rand(60, 120);
				$this->stayTime = -1;
				$this->walkTime = $this->panicTime + mt_rand(0, 40);
				$this->findTarget();
			}
		}
	}

	public function canSaveWithChunk() : bool{
		return false;
	}

	public function getStackValue() : int{
		return $this->stackValue;
	}

	public function addStackValue(int $value = 1) : void{
		$this->stackValue += $value;
	}

	public function subStackValue() : void{
		$this->stackValue -= 1;
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		parent::entityBaseTick($tickDiff);

		if($this->ticksLived % 100 == 0){
			/** @var self $nearest */
			$nearest = $this->getNearestEntity(self::STACK_DISTANCE);
			if($nearest != null){
				$stack = $nearest->getStackValue();
				if($stack <= $this->getStackValue() && !$this->isFlaggedForDespawn()){
					$nearest->flagForDespawn();
					$this->addStackValue($stack);
					$this->setNametag(TextFormat::AQUA . TextFormat::BOLD . $this->getName() . TextFormat::YELLOW . " (" . $this->getStackValue() . ")" . TextFormat::RESET . TextFormat::GREEN . " " . $this->getHealth() . "/" . $this->getMaxHealth());
				}
			}
		}

		$this->walk();

		return $this->isAlive();
	}

	public function getNearestEntity(float $maxDistance) : ?Entity{
		$pos = $this->getPosition();

		$currentTargetDistSq = $maxDistance ** 2;
		$currentTarget = null;

		foreach($pos->getWorld()->getNearbyEntities($this->getBoundingBox()->expandedCopy($maxDistance, $maxDistance, $maxDistance), $this) as $entity){
			if(!$entity instanceof Animal || $entity::getNetworkTypeId() !== $this::getNetworkTypeId() || $entity->isClosed() || $entity->isFlaggedForDespawn() || !$entity->isAlive()){
				continue;
			}
			$distSq = $entity->getPosition()->distanceSquared($pos);
			if($distSq < $currentTargetDistSq){
				$currentTargetDistSq = $distSq;
				$currentTarget = $entity;
			}
		}
		return $currentTarget;
	}

	public function atTarget() : bool{
		return $this->getPosition()->distance($this->getTarget()) <= 3;
	}

	public function hasTarget() : bool{
		return $this->getTarget() !== null;
	}

	public function getTarget() : ?Vector3{
		return $this->target;
	}

	public function findTarget() : void{
		$this->target = $this->getPosition()->add(mt_rand(-10, 10), -1, mt_rand(-10, 10));
	}

	public function walk() : void{
		if(!$this->hasTarget() || $this->atTarget()){
			$this->findTarget();
			return;
		}
		if($this->panicTime > 0){
			$this->panicTime--;
		}

		if($this->stayTime > 0){
			$this->stayTime--;
			return;
		}elseif($this->stayTime == 0 && $this->walkTime == -1){
			$this->walkTime = mt_rand(100, 200);
			$this->stayTime = -1;
			$this->findTarget();
		}

		if($this->walkTime > 0){
			$this->walkTime--;

			$x = $this->getTarget()->x - $this->getLocation()->x;
			$y = $this->getTarget()->y - $this->getLocation()->y;
			$z = $this->getTarget()->z - $this->getLocation()->z;

			if($x * $x + $z * $z < 4 + $this->getScale()) {
				$this->motion->x = 0;
				$this->motion->z = 0;
			} else {
				$this->motion->x = $this->getSpeed() * 0.35 * ($x / (abs($x) + abs($z)));
				$this->motion->z = $this->getSpeed() * 0.35 * ($z / (abs($x) + abs($z)));
			}
			$this->setRotation(rad2deg(atan2(-$x, $z)), 0);

		}elseif($this->walkTime == 0 && $this->stayTime == -1){
			$this->stayTime = mt_rand(60, 100);
			$this->walkTime = -1;
		}
	}
}