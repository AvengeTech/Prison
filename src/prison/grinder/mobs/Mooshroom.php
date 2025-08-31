<?php namespace prison\grinder\mobs;

use pocketmine\block\VanillaBlocks;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\VanillaItems;

class Mooshroom extends Animal{



	public $width = 1.5;
	public $height = 1.2;

	public function getName() : string{
		return "Mooshroom";
	}

	public function getMaxHealth() : int{
		return 10;
	}

	public function getDrops() : array{
		$drops = [VanillaItems::LEATHER()->setCount(mt_rand(0, 2))];
		if(mt_rand(0, 1) == 1) $drops[] = VanillaBlocks::BROWN_MUSHROOM()->asItem()->setCount(mt_rand(1, 4));
		if(mt_rand(0, 1) == 1) $drops[] = VanillaBlocks::RED_MUSHROOM()->asItem()->setCount(mt_rand(1, 4));
		if($this->isOnFire()){
			$drops[] = VanillaItems::STEAK()->setCount(mt_rand(1, 3));
		}else{
			$drops[] = VanillaItems::RAW_BEEF()->setCount(mt_rand(1, 3));
		}
		return $drops;
	}

	protected function getInitialSizeInfo(): EntitySizeInfo{
		return new EntitySizeInfo($this->height, $this->width);
	}

	public static function getNetworkTypeId(): string{
		return "minecraft:mooshroom";
	}

}