<?php namespace prison\entity;

use core\utils\ItemRegistry;
use pocketmine\color\Color;
use pocketmine\entity\{
	Entity,
	effect\VanillaEffects,
	effect\EffectInstance,
	projectile\Throwable
};
use pocketmine\player\Player;
use pocketmine\world\particle\DustParticle;

use prison\Prison;
use prison\PrisonPlayer;

use core\utils\PlaySound;

class HasteBomb extends Throwable{

	const HASTE_CD = 600;

	public function entityBaseTick(int $tickDiff = 1): bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);

		$thrower = $this->getOwningEntity();
		if($thrower == null){
			$this->flagForDespawn();
			return true;
		}

		$this->getWorld()->addParticle($this->getPosition(), new DustParticle(new Color(255, 255, 0)));
		if($this->isCollided){
			if($this->doHasteSplash() <= 0){
				$bomb = ItemRegistry::HASTE_BOMB();
				$bomb->init();
				if($thrower instanceof Player){
					unset(Prison::getInstance()->getEnchantments()->hbcd[$thrower->getName()]);
					if($thrower->getInventory()->canAddItem($bomb)){
						$thrower->getInventory()->addItem($bomb);
					}else{
						$this->getWorld()->dropItem($this->getPosition(), $bomb);
					}
				}else{
					$this->getWorld()->dropItem($this->getPosition(), $bomb);
				}
			}
			$this->flagForDespawn();
			$hasUpdate = true;
		}

		return $hasUpdate;
	}

	public function onCollideWithEntity(Entity $entity){
		$thrower = $this->getOwningEntity();
		if($this->doHasteSplash() <= 0){
			$bomb = ItemRegistry::HASTE_BOMB();
			$bomb->init();
			if($thrower instanceof Player){
				unset(Prison::getInstance()->getEnchantments()->hbcd[$thrower->getName()]);
				if($thrower->getInventory()->canAddItem($bomb)){
					$thrower->getInventory()->addItem($bomb);
				}else{
					$this->getWorld()->dropItem($this->getPosition(), $bomb);
				}
			}else{
				$this->getWorld()->dropItem($this->getPosition(), $bomb);
			}
		}
		$this->flagForDespawn();
	}

	public function doHasteSplash() : int{
		$ench = Prison::getInstance()->getEnchantments();
		$affected = 0;
		foreach($this->getViewers() as $viewer){
			/** @var PrisonPlayer $viewer */
			if($viewer instanceof Player && $viewer->getPosition()->distance($this->getPosition()) <= 3){
				if($viewer->getEffects()->has(VanillaEffects::HASTE())){
					$effect = $viewer->getEffects()->get(VanillaEffects::HASTE());
					$effect = $effect->setDuration(min(20 * 60 * 30, $effect->getDuration() + (20 * 60 * 5)));
					$effect->setAmplifier(1);
				}else{
					$effect = new EffectInstance(VanillaEffects::HASTE(), 300 * 20, 1);
				}
				$viewer->getEffects()->remove(VanillaEffects::HASTE());
				$viewer->addEffect($effect);
				$affected++;
			}
		}
		if($affected > 0){
			$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "mob.zombie.remedy"));
		}
		return $affected;
	}

	public static function getNetworkTypeId(): string{
		return "minecraft:shulker_bullet";
	}

	public function canSaveWithChunk() : bool{
		return false;
	}

}