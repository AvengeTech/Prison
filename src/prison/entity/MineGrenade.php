<?php namespace prison\entity;

use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\block\{
	BlockIdentifier,
	BlockLegacyIds,
	VanillaBlocks,
	Block
};
use pocketmine\entity\{
	Entity,
	projectile\Throwable
};
use pocketmine\item\{
	Item,
	VanillaItems
};
use pocketmine\math\Vector3;

use prison\Prison;
use prison\item\MineGrenade as MineGrenadeItem;
use prison\PrisonPlayer;
use prison\settings\PrisonSettings;

/** 
 * Outdated & unimplemented entity
 * @deprecated 1.9.0
 */
class MineGrenade extends Throwable{

	public function entityBaseTick(int $tickDiff = 1): bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);

		$thrower = $this->getOwningEntity();
		if(is_null($thrower) || !$thrower instanceof PrisonPlayer){
			$this->flagForDespawn();
			return true;
		}

		if($this->isCollided){
			$block = $this->getWorld()->getBlock($this->getPosition()->subtract(0, 2, 0));
			$mine = ($session = $thrower->getGameSession()->getMines())->getMine();
			if($mine->inMine($block->getPosition())){
				$drops = [];
				$explode = 0;

				for($x = $block->getPosition()->getX() - 2; $x <= $block->getPosition()->getX() + 2; $x++){
					for($y = $block->getPosition()->getY() - 2; $y <= $block->getPosition()->getY() + 2; $y++){
						for($z = $block->getPosition()->getZ() - 2; $z <= $block->getPosition()->getZ() + 2; $z++){
							$b = $this->getWorld()->getBlock(new Vector3($x, $y, $z));
							if($mine->inMine($b->getPosition())){
								if(mt_rand(0,1) == 1){
									foreach($b->getDropsForCompatibleTool(VanillaItems::AIR()) as $drop) $drops[] = $drop;
									$this->getWorld()->setBlock($b->getPosition(), VanillaBlocks::AIR());
									$mine->addTotalMined();
									if(mt_rand(1, 5) == 1 && $explode <= 5){
										Prison::getInstance()->getEnchantments()->calls->explosion($this);
										$explode++;
									}
								}
							}
						}
					}
				}

				foreach($drops as $key => $drop){
					if($drop->equals(VanillaBlocks::IRON_ORE()->asItem(), false, false)){
						$drops[$key] = VanillaItems::IRON_INGOT()->setCount($drop->getCount());
					}
					if($drop->equals(VanillaBlocks::GOLD_ORE()->asItem(), false, false)){
						$drops[$key] = VanillaItems::GOLD_INGOT()->setCount($drop->getCount());
					}
				}

				if($thrower->getGameSession()->getSettings()->getSetting(PrisonSettings::AUTOSELL) && $thrower->getRankHierarchy() >= 5){
					$drops = Prison::getInstance()->getShops()->sellDrops($thrower, $drops);
				}
				foreach($drops as $drop){
					$thrower->getInventory()->addItem($drop);
				}
			}else{
				$nuke = new MineNukeItem();
				$nuke->init();
				if($thrower instanceof Player){
					if(isset(Prison::getInstance()->getEnchantments()->gncd[$thrower->getName()])){
						Prison::getInstance()->getEnchantments()->gncd[$thrower->getName()]["total"]++;
					}
					if($thrower->getInventory()->canAddItem($nuke)){
						$thrower->getInventory()->addItem($nuke);
					}else{
						$this->getWorld()->dropItem($this->getPosition(), $nuke);
					}
				}else{
					$this->getWorld()->dropItem($this->getPosition(), $nuke);
				}
			}

			$this->flagForDespawn();
			$hasUpdate = true;
		}

		return $hasUpdate;
	}

	public function onCollideWithEntity(Entity $entity){
		$block = $this->getWorld()->getBlock($this->getPosition()->subtract(0,2,0));
		$thrower = $this->getOwningEntity();
		$mine = Prison::getInstance()->getMines()->getSessionManager()->getSession($thrower)->getMine();

		if($mine->inMine($block->getPosition())){
			$drops = [];
			$explode = 0;

			for($x = $block->getPosition()->getX() - 2; $x <= $block->getPosition()->getX() + 2; $x++){
				for($y = $block->getPosition()->getY() - 2; $y <= $block->getPosition()->getY() + 2; $y++){
					for($z = $block->getPosition()->getZ() - 2; $z <= $block->getPosition()->getZ() + 2; $z++){
						$b = $this->getWorld()->getBlock(new Vector3($x, $y, $z));
						if($mine->inMine($b->getPosition())){
							if(mt_rand(0,1) == 1){
								foreach($b->getDropsForCompatibleTool(VanillaBlocks::AIR()->asItem()) as $drop) $drops[] = $drop;
								$this->getWorld()->setBlock($b->getPosition(), VanillaBlocks::AIR());
								if(mt_rand(1, 5) == 1 && $explode <= 5){
									Prison::getInstance()->getEnchantments()->calls->explosion($this);
									$explode++;
								}
							}
						}
					}
				}
			}
			foreach($drops as $drop){
				if($drop->getId() == BlockLegacyIds::IRON_ORE){
					$drop = VanillaItems::IRON_INGOT()->setCount($drop->getCount());
				}
				if($drop->getId() == BlockLegacyIds::GOLD_ORE){
					$drop = VanillaItems::GOLD_INGOT()->setCount($drop->getCount());
				}
				$thrower->getInventory()->addItem($drop);
			}
		}else{
			$nuke = new MineGrenadeItem();
			$nuke->init();
			if($thrower instanceof Player){
				if(isset(Prison::getInstance()->getEnchantments()->gncd[$thrower->getName()])){
					Prison::getInstance()->getEnchantments()->gncd[$thrower->getName()]["total"]++;
				}
				if($thrower->getInventory()->canAddItem($nuke)){
					$thrower->getInventory()->addItem($nuke);
				}else{
					$this->getWorld()->dropItem($this->getPosition(), $nuke);
				}
			}else{
				$this->getWorld()->dropItem($this->getPosition(), $nuke);
			}
		}

		$this->flagForDespawn();
	}

	public static function getNetworkTypeId(): string{
		return "minecraft:shulker_bullet";
	}

	public function canSaveWithChunk() : bool{
		return false;
	}

}