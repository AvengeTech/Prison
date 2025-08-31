<?php namespace prison\grinder\mobs;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\math\Vector3;

use core\utils\GenericSound;

class Cow extends Animal{

	public $width = 1.5;
	public $height = 1.2;

	public function getName() : string{
		return "Cow";
	}

	public function getDrops() : array{
		$drops = [VanillaItems::LEATHER()->setCount(mt_rand(0, 2))];
		if($this->isOnFire()){
			$drops[] = VanillaItems::STEAK()->setCount(mt_rand(1, 3));
		}else{
			$drops[] = VanillaItems::RAW_BEEF()->setCount(mt_rand(1, 3));
		}
		return $drops;
	}

	public function getMaxHealth() : int{
		return 10;
	}

	public function onInteract(Player $player, Vector3 $clickPos) : bool{
		$item = $player->getInventory()->getItemInHand();
		$slot = $player->getInventory()->getHeldItemIndex();
		if($item->getTypeId() == ItemTypeIds::BUCKET){
			if(($inventory = $player->getInventory())->canAddItem(($i = VanillaItems::MILK_BUCKET()))){
				$item->pop();
				$inventory->setItem($slot, $item);
				$inventory->addItem($i);
				$player->getWorld()->addSound($player->getPosition(), new GenericSound($player->getPosition(), 46));
			}
		}
		return true;
	}

	protected function getInitialSizeInfo(): EntitySizeInfo{
		return new EntitySizeInfo($this->height, $this->width);
	}

	public static function getNetworkTypeId(): string{
		return "minecraft:cow";
	}

}