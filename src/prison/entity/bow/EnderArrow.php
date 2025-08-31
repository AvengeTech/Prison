<?php namespace prison\entity\bow;

use pocketmine\world\{
	particle\PortalParticle,
	sound\EndermanTeleportSound
};
use pocketmine\player\Player;

class EnderArrow extends EArrow{

	public function entityBaseTick(int $tickDiff = 1): bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);

		if($this->onGround or $this->isCollided or $this->getOwningEntity() == null){
			$this->flagForDespawn();
			$hasUpdate = true;

			$this->getWorld()->addSound($this->getOwningEntity()->getPosition(), new EndermanTeleportSound());
			$this->getOwningEntity()->teleport($this->getPosition(), $this->getOwningEntity()->getLocation()->getYaw(), $this->getOwningEntity()->getLocation()->getPitch());
			$this->getWorld()->addSound($this->getOwningEntity()->getPosition(), new EndermanTeleportSound());
		}
		for($i = 0; $i <= mt_rand(2, 4); $i++){
			$this->getWorld()->addParticle($this->getPosition()->add(mt_rand(-10,10) * 0.1, mt_rand(-10,10) * 0.1, mt_rand(-10,10) * 0.1), new PortalParticle());
		}

		return $hasUpdate;
	}

	public function onCollideWithPlayer(Player $player) : void{
		$this->getWorld()->addSound($this->getOwningEntity()->getPosition(), new EndermanTeleportSound());
		$this->getOwningEntity()->teleport($this->getPosition(), $this->getOwningEntity()->getLocation()->getYaw(), $this->getOwningEntity()->getLocation()->getPitch());
		$this->getWorld()->addSound($this->getOwningEntity()->getPosition(), new EndermanTeleportSound());

		parent::onCollideWithPlayer($player);
	}

}