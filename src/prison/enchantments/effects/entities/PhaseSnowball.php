<?php namespace prison\enchantments\effects\entities;

use pocketmine\entity\projectile\Snowball;
use pocketmine\entity\Entity;

class PhaseSnowball extends Snowball{

	public function canCollideWith(Entity $entity) : bool{
		return false;
	}

	public function getResultDamage() : int{
		return 0;
	}

	public function canSaveWithChunk() : bool{
		return false;
	}

}