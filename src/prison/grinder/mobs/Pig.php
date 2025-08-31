<?php namespace prison\grinder\mobs;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\VanillaItems;

class Pig extends Animal{

	public $width = 1.5;
	public $height = 1.0;

	public function getName() : string{
		return "Pig";
	}

	public function getDrops() : array{
		if($this->isOnFire()){
			return [VanillaItems::COOKED_PORKCHOP()->setCount(mt_rand(1, 3))];
		}else{
			return [VanillaItems::RAW_PORKCHOP()->setCount(mt_rand(1, 3))];
		}
	}

	public function getMaxHealth() : int{
		return 10;
	}

	protected function getInitialSizeInfo(): EntitySizeInfo{
		return new EntitySizeInfo($this->height, $this->width);
	}

	public static function getNetworkTypeId(): string{
		return "minecraft:pig";
	}
	
}