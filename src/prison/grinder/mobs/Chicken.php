<?php namespace prison\grinder\mobs;

use core\utils\conversion\LegacyItemIds;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\math\Vector3;
use pocketmine\world\{
	sound\PopSound
};

use core\utils\GenericSound;

class Chicken extends Animal{

	public $width = 1.0;
	public $height = 0.8;

	public $eggTimer = 0;

	public function __construct(Location $loc){
		parent::__construct($loc);

		$this->resetEggTimer();
	}

	public function getName() : string{
		return "Chicken";
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		parent::entityBaseTick($tickDiff);

		if($this->eggTimer > 1){
			$this->eggTimer--;
			if($this->eggTimer <= 1){
				$this->layEgg();
			}
		}

		return $this->isAlive();
	}

	public function getDrops() : array{
		$drops = [VanillaItems::FEATHER()->setCount(mt_rand(0, 2))];
		if($this->isOnFire()){
			$drops[] = VanillaItems::COOKED_CHICKEN()->setCount(mt_rand(0, 1));
		}else{
			$drops[] = VanillaItems::RAW_CHICKEN()->setCount(mt_rand(0, 1));
		}
		return $drops;
	}

	public function getMaxHealth() : int{
		return 4;
	}

	public function onInteract(Player $player, Vector3 $clickPos) : bool{
		if($player->getName() == "sn3akrr"){
			$item = $player->getInventory()->getItemInHand();
			$slot = $player->getInventory()->getHeldItemIndex();
			if($item->getTypeId() == ItemTypeIds::BUCKET){
				if(($inventory = $player->getInventory())->canAddItem(($i = VanillaItems::BUCKET()))){
					$item->pop();
					$inventory->setItem($slot, $item);
					$inventory->addItem($i);
					$player->getWorld()->addSound($player->getPosition(), new GenericSound($player->getPosition(), 46));
				}
			}elseif($item->getTypeId() == VanillaItems::AIR()->getTypeId()){
				$this->layEgg();
			}
		}
		return true;
	}

	public function layEgg() : void{
		$this->getWorld()->addSound($this->getPosition(), new PopSound());
		$this->getWorld()->dropItem($this->getPosition(), VanillaItems::EGG());
		$this->resetEggTimer();
	}

	public function resetEggTimer() : void{
		$this->eggTimer = mt_rand(20 * 30, 20 * 60);
	}

	protected function getInitialSizeInfo(): EntitySizeInfo{
		return new EntitySizeInfo($this->height, $this->width);
	}

	public static function getNetworkTypeId(): string{
		return "minecraft:chicken";
	}
}