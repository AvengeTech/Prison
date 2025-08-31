<?php namespace prison\enchantments\effects\tasks;

use pocketmine\network\mcpe\protocol\{
	AddActorPacket,
	types\entity\PropertySyncData
};
use pocketmine\entity\Entity;
use pocketmine\world\particle\MobSpawnParticle;

use core\utils\PlaySound;

class ThorsWrathAnimationTask extends AnimationTask{

	public function onRun() : void{
		$timer = $this->getTimer();
		if($this->isNew()){
			($dp = $this->getDeathPos())->getWorld()->addSound($dp, new PlaySound($dp, "ambient.weather.lightning.impact"));
			$this->zap();
		}else{
			switch($timer){
				case 20:
				case 40:
					$this->zap();
					break;
				default:
					if($timer %3 == 0){
						$this->cloud();
					}
					break;
			}
		}

		if($this->isLastCall()){
			$this->zap();
		}

		parent::onRun();
	}

	public function zap() : void{
		$pos = $this->getDeathPos();

		$pk = new AddActorPacket();
		$pk->type = "minecraft:lightning_bolt";
		$pk->actorRuntimeId = $pk->actorUniqueId = Entity::nextRuntimeId();
		$pk->position = $pos->asVector3();
		$pk->yaw = $pk->pitch = 0;
		$pk->syncedProperties = new PropertySyncData([], []);
		foreach($pos->getWorld()->getPlayers() as $p){
			$p->getNetworkSession()->sendDataPacket($pk);
		}
	}

	public function cloud() : void{
		$pos = $this->getDeathPos();
		$pos->getWorld()->addParticle($pos->add(mt_rand(0, 10) / 10, 0, mt_rand(0, 10) / 10), new MobSpawnParticle());
	}


}