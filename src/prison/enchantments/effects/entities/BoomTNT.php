<?php namespace prison\enchantments\effects\entities;

use pocketmine\entity\object\PrimedTNT;
use pocketmine\world\sound\PopSound;
use pocketmine\world\particle\HugeExplodeSeedParticle;

use core\utils\PlaySound;

class BoomTNT extends PrimedTNT{

	public $jumps = 0;
	public $bye = -1;

	public function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->closed){
			return false;
		}

		$hasUpdate = parent::entityBaseTick($tickDiff);

		if(!$this->isFlaggedForDespawn()){
			if($this->bye > -1){
				$this->bye--;
				if($this->bye == 0){
					$this->getWorld()->addParticle($this->getPosition(), new HugeExplodeSeedParticle());
					$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "random.explode"));
					$this->flagForDespawn();
					return false;
				}
				return true;
			}

			if($this->onGround)
				$this->jump();
		}

		return $hasUpdate;
	}

	public function getName() : string{
		return "Boom TNT";
	}

	public function canSaveWithChunk() : bool{
		return false;
	}

	public function jump() : void{
		if($this->isFlaggedForDespawn()) return;
		$this->motion->y = 0.25;
		$this->getWorld()->addSound($this->getPosition(), new PopSound());
		$this->jumps++;
		if($this->jumps %5 == 0){
			$this->bye = 4;
			//$this->getWorld()->addParticle(new HugeExplodeSeedParticle($this));
			//$this->getWorld()->addSound(new PlaySound($this, "random.explode"));
			//$this->flagForDespawn();
		}
	}

}