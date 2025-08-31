<?php namespace prison\entity;

use core\utils\ItemRegistry;
use pocketmine\player\Player;
use pocketmine\block\{
	BlockLegacyIds,
    BlockTypeIds,
    VanillaBlocks,
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
use prison\item\MineNuke as MineNukeItem;
use prison\PrisonPlayer;
use prison\PrisonSession;
use prison\settings\PrisonSettings;

class MineNuke extends Throwable{

	public function entityBaseTick(int $tickDiff = 1): bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);

		/** @var PrisonPlayer $thrower */
		$thrower = $this->getOwningEntity();
		if($thrower === null){
			$this->flagForDespawn();
			return true;
		}

		if($this->isCollided){
			$block = $this->getWorld()->getBlock($this->getPosition()->subtract(0, 2, 0));
			$mine = ($session = $thrower->getGameSession()->getMines())->getMine();
			if($mine->inMine($block->getPosition())){
				/** @var Item[] */
				$drops = [];
				$explode = 0;

				for($x = $block->getPosition()->getX() - 6; $x <= $block->getPosition()->getX() + 6; $x++){
					for($y = $block->getPosition()->getY() - 6; $y <= $block->getPosition()->getY() + 6; $y++){
						for($z = $block->getPosition()->getZ() - 6; $z <= $block->getPosition()->getZ() + 6; $z++){
							$b = $this->getWorld()->getBlock(new Vector3($x, $y, $z));
							if($mine->inMine($b->getPosition())){
								if(mt_rand(0,1) == 1){
									foreach($b->getDropsForCompatibleTool(VanillaBlocks::AIR()->asItem()) as $drop) $drops[] = $drop;
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
					if($drop->equals(VanillaBlocks::IRON_ORE()->asItem(), false, false) || $drop->equals(VanillaItems::RAW_IRON(), false, false)){
						$drops[$key] = VanillaItems::IRON_INGOT()->setCount($drop->getCount());
					}
					if($drop->equals(VanillaBlocks::GOLD_ORE()->asItem(), false, false) || $drop->equals(VanillaItems::RAW_GOLD(), false, false)){
						$drops[$key] = VanillaItems::GOLD_INGOT()->setCount($drop->getCount());
					}
				}

				if (($thrower->getGameSession()->getSettings()->getSetting(PrisonSettings::AUTOSELL) && $thrower->getRankHierarchy() >= 5) || $thrower->getGameSession()->getShops()->isActive()) {
					$drops = Prison::getInstance()->getShops()->sellDrops($thrower, $drops);
				}
				/** @var Item[] $drops */
				foreach($drops as $drop){
					if($drop->isNull() || $drop->equals(VanillaItems::AIR(), false, false)) continue;
					
					$thrower->getInventory()->addItem($drop);
				}
			}else{
				$nuke = ItemRegistry::MINE_NUKE();
				$nuke->init();
				if($thrower instanceof Player){
					unset(Prison::getInstance()->getEnchantments()->nukecd[$thrower->getName()]);
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
		/** @var PrisonPlayer */
		$thrower = $this->getOwningEntity();
		/** @var PrisonSession */
		$session = Prison::getInstance()->getSessionManager()->getSession($thrower);
		$mine = $session->getMines()->getMine();

		if($mine->inMine($block->getPosition())){
			/** @var Item[] */
			$drops = [];
			$explode = 0;

			for($x = $block->getPosition()->getX() - 6; $x <= $block->getPosition()->getX() + 6; $x++){
				for($y = $block->getPosition()->getY() - 6; $y <= $block->getPosition()->getY() + 6; $y++){
					for($z = $block->getPosition()->getZ() - 6; $z <= $block->getPosition()->getZ() + 6; $z++){
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
				if($drop->equals(VanillaBlocks::IRON_ORE()->asItem(), false, false)){
					$drop = VanillaItems::IRON_INGOT()->setCount($drop->getCount());
				}
				if($drop->equals(VanillaBlocks::GOLD_ORE()->asItem(), false, false)){
					$drop = VanillaItems::GOLD_INGOT()->setCount($drop->getCount());
				}

				if($drop->isNull() || $drop->equals(VanillaItems::AIR(), false, false)) continue;
				
				$thrower->getInventory()->addItem($drop);
			}
		}else{
			$nuke = ItemRegistry::MINE_NUKE();
			$nuke->init();
			if($thrower instanceof Player){
				unset(Prison::getInstance()->getEnchantments()->nukecd[$thrower->getName()]);
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