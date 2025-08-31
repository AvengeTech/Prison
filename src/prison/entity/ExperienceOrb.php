<?php namespace prison\entity;

use pocketmine\entity\object\ExperienceOrb as PMXP;

use pocketmine\entity\Human;
use pocketmine\player\Player;

class ExperienceOrb extends PMXP{

	public function getTargetPlayer() : ?Human{
		$entity = parent::getTargetPlayer();
		if($entity instanceof Player){
			return $entity;
		}

		return null;
	}

	public function setTargetPlayer(?Human $player) : void{
		if($player instanceof Human){
			if(!$player instanceof Player){
				$this->targetPlayerRuntimeId = null;
			}else{
				parent::setTargetPlayer($player);
			}
		}else{
			parent::setTargetPlayer($player);
		}
	}

}