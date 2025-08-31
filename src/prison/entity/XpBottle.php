<?php namespace prison\entity;

use pocketmine\{
	player\Player,
};
use pocketmine\entity\{
	Entity,
	projectile\Throwable
};

class XpBottle extends Throwable{

	public $ticks = 0;

	public function entityBaseTick(int $tickDiff = 1): bool{
		$this->ticks++;
		$hasUpdate = parent::entityBaseTick($tickDiff);
		if($this->ticks > 1200 or $this->isCollided){
			$player = $this->getOwningEntity();
			if($player instanceof Player){
				$player->getXpManager()->addXp(mt_rand(2, 8));
			}else{
				$this->getWorld()->dropExperience($this->getLocation(), mt_rand(2, 8));
			}
			$this->flagForDespawn();
			$hasUpdate = true;
		}

		return $hasUpdate;
	}

	public function onCollideWithEntity(Entity $entity){
		if($entity instanceof Player){
			$entity->getXpManager()->addXp(mt_rand(2, 8));
		}else{
			$this->getWorld()->dropExperience($this->getLocation(), mt_rand(2, 8));
		}
		$this->flagForDespawn();
	}

	public static function getNetworkTypeId(): string{
		return "minecraft:xp_bottle";
	}
}