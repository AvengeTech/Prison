<?php namespace prison\enchantments;

use pocketmine\event\{
	block\BlockBreakEvent,
	entity\EntityDamageByEntityEvent,
	entity\EntityShootBowEvent,
};
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\entity\Location;
use pocketmine\world\{
	Position,
	Explosion,
	particle\ExplodeParticle,
	particle\FlameParticle,
	particle\SplashParticle,
	particle\BlockBreakParticle as DestroyBlockParticle,

	sound\AnvilFallSound,
	sound\LaunchSound
};
use pocketmine\network\mcpe\protocol\{
	AddActorPacket,
	LevelSoundEventPacket,
	RemoveActorPacket,
	types\LevelSoundEvent,
	types\entity\PropertySyncData
};
use pocketmine\entity\effect\{EffectInstance, VanillaEffects};
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\entity\Living;
use pocketmine\entity\projectile\Arrow;
use pocketmine\item\ItemTypeIds;
use pocketmine\scheduler\ClosureTask;

use prison\Prison;
use prison\entity\{
	bow\EnderArrow,
	bow\PerishArrow,
	bow\SniperArrow
};
use prison\settings\PrisonSettings;
use prison\PrisonPlayer;

use core\utils\{
	TextFormat,
	GenericSound,
    ItemRegistry,
    PlaySound
};
use core\utils\conversion\LegacyBlockIds;
use core\utils\conversion\LegacyItemIds;
use pocketmine\block\Block;
use pocketmine\entity\Human;
use pocketmine\item\VanillaItems;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\sound\ExplodeSound;
use prison\techits\item\TechitNote;

class Calls extends EnchantmentData{
	use SingletonTrait;

	public array $event = [];

	public array $equip = [];
	public array $unequip = [];

	public array $task = [];

	public function __construct(){
		self::setInstance($this);
		$this->event = [
			self::ZEUS => function(EntityDamageByEntityEvent $e, int $level){
				$hurt = $e->getEntity();
				$killer = $e->getDamager();

				switch ($level) {
					default:
					case 1:
						$chance = mt_rand(1, 19) == 1;
						break;
					case 2:
						$chance = mt_rand(1, 12) == 1;
						break;
					case 3:
						$chance = mt_rand(1, 8) == 1;
						break;
				}
				
				if ($chance) {
					$this->strikeLightning($hurt->getPosition());
					$e->setBaseDamage($e->getBaseDamage() * (($level / 4) + 1));
				}
			},
			self::KEY_THEFT => function(EntityDamageByEntityEvent $e, int $level){
				switch ($level) {
					default:
					case 1:
						$chance = mt_rand(1, 15) == 1;
						break;
					case 2:
						$chance = mt_rand(1, 10) == 1;
						break;
					case 3:
						$chance = mt_rand(1, 5) == 1;
						break;
				}

				/** @var PrisonPlayer $player */
				$player = $e->getEntity();
				/** @var PrisonPlayer $killer */
				$killer = $e->getDamager();
				if($chance && $e->getFinalDamage() >= $player->getHealth()){
					if($player instanceof Player){
						$stole = [
							"iron" => 0,
							"gold" => 0,
							"diamond" => 0,
							"emerald" => 0,
							"vote" => 0
						];

						for($i = 1; $i <= $max = mt_rand(1, 3); $i++){
							$keytype = $this->getRandomKeyType($player, $stole);
							if($keytype !== false){
								$player->getGameSession()->getMysteryBoxes()->takeKeys($keytype, ($amt = mt_rand(1, $level)));
								$killer->getGameSession()->getMysteryBoxes()->addKeys($keytype, $amt);
								$stole[$keytype]++;
							}
						}

						$count = 0;
						foreach($stole as $type => $amount){
							if($amount <= 0){
								unset($stole[$type]);
							}else{
								$count += $amount;
							}
						}

						if($count > 0 && $killer instanceof Player){
							$killer->sendMessage(TextFormat::AQUA . "Stole " . TextFormat::YELLOW . $count . " keys " . TextFormat::AQUA . "from " . TextFormat::RED . $player->getName() . ":");
							foreach($stole as $type => $amount){
								$killer->sendMessage(TextFormat::GRAY . " - " . TextFormat::GREEN . "x" . $amount . " " . $type . " keys");
							}
						}
					}
				}
			},
			self::LIFESTEAL => function(EntityDamageByEntityEvent $e, int $level){
				switch ($level) {
					default:
					case 1:
						$chance = mt_rand(1, 20) == 1;
						break;
					case 2:
						$chance = mt_rand(1, 15) == 1;
						break;
					case 3:
						$chance = mt_rand(1, 10) == 1;
						break;
				}

				if ($chance) {
					$e->getDamager()->setHealth($e->getDamager()->getHealth() + ($e->getFinalDamage() / 2));
				}
			},
			self::KABOOM => function(EntityDamageByEntityEvent $e, int $level){
				switch ($level) {
					default:
					case 1:
						$chance = mt_rand(1, 19) == 1;
						break;
					case 2:
						$chance = mt_rand(1, 11) == 1;
						break;
					case 3:
						$chance = mt_rand(1, 9) == 1;
						break;
				}

				if ($chance) {
					$this->explosion($e->getEntity());
					$e->setBaseDamage($e->getBaseDamage() * (($level / 2) + 1));
					$e->setKnockback($e->getKnockback() * 1.5);
				}
			},
			self::HADES => function(EntityDamageByEntityEvent $e, int $level){
				switch ($level) {
					default:
					case 1:
						$chance = mt_rand(1, 15) == 1;
						break;
					case 2:
						$chance = mt_rand(1, 10) == 1;
						break;
					case 3:
						$chance = mt_rand(1, 5) == 1;
						break;
				}

				if($chance){
					$entity = $e->getEntity();
					$killer = $e->getDamager();

					for($i = 1; $i <= $level * 3; $i++){
						$entity->getWorld()->addParticle($entity->getPosition()->add(mt_rand(-10, 10) * 0.1, mt_rand(0, 20) * 0.1, mt_rand(-10, 10) * 0.1), new FlameParticle());
					}

					$entity->setOnFire($level * mt_rand(1, 2));

					$e->setBaseDamage($e->getBaseDamage() + $level);

					if ($entity instanceof Human) {
						$entity->getEffects()->add(new EffectInstance(VanillaEffects::WITHER(), 20 * ($level + (2 * $level)), 1));
					}
				}
			},
			self::OOF => function(EntityDamageByEntityEvent $e, int $level){
				if(mt_rand(1, 3) == 1) {
					/** @var PrisonPlayer $entity */
					$entity = $e->getEntity();
					foreach($e->getEntity()->getViewers() as $viewer) {
						/** @var PrisonPlayer $viewer */
						$viewer->playSound("random.hurt", $e->getEntity()->getPosition());
					}
					if($entity instanceof Player) $entity->playSound("random.hurt");
				}
			},
			self::FROST => function(EntityDamageByEntityEvent $e, int $level){
				switch ($level) {
					default:
					case 1:
						$chance = mt_rand(1, 15) == 1;
						break;
					case 2:
						$chance = mt_rand(1, 10) == 1;
						break;
					case 3:
						$chance = mt_rand(1, 5) == 1;
						break;
				}

				$entity = $e->getEntity();
				if($chance && $entity instanceof Living){
					$entity->getEffects()->add(new EffectInstance(VanillaEffects::SLOWNESS(), 20 * ($level * 4)));
				}
			},
			self::DAZE => function(EntityDamageByEntityEvent $e, int $level){
				switch ($level) {
					default:
					case 1:
						$chance = mt_rand(1, 15) == 1;
						break;
					case 2:
						$chance = mt_rand(1, 10) == 1;
						break;
					case 3:
						$chance = mt_rand(1, 7) == 1;
						break;
				}

				$entity = $e->getEntity();
				if ($chance && $entity instanceof Living) {
					$entity->getEffects()->add(new EffectInstance(VanillaEffects::NAUSEA(), 20 * ($level + (2 * $level)), $level - 1));
				}
			},
			self::POISON => function(EntityDamageByEntityEvent $e, int $level){
				switch ($level) {
					default:
					case 1:
						$chance = mt_rand(1, 20) == 1;
						break;
					case 2:
						$chance = mt_rand(1, 10) == 1;
						break;
				}

				$entity = $e->getEntity();
				if ($chance && $entity instanceof Living) {
					$entity->getEffects()->add(new EffectInstance(VanillaEffects::POISON(), 20 * ($level * 3), $level - 1));
				}
			},
			self::UPLIFT => function(EntityDamageByEntityEvent $e, int $level){
				if (mt_rand(1, 5) == 1) {
					$e->setBaseDamage($e->getBaseDamage() + 1);
					$e->setKnockback($e->getKnockback() * mt_rand(3, 6));
				}
			},
			self::BLEED => function(EntityDamageByEntityEvent $e, int $level){
				$player = $e->getEntity();
				if(!$player instanceof Player) return;

				$player->getWorld()->addParticle($player->getPosition(), new DestroyBlockParticle(VanillaBlocks::REDSTONE()));

				switch ($level) {
					default:
					case 1:
						$chance = mt_rand(1, 15) == 1;
						break;
					case 2:
						$chance = mt_rand(1, 10) == 1;
						break;
					case 3:
						$chance = mt_rand(1, 5) == 1;
						break;
				}

				if($chance && $e->getDamager() instanceof Player){
					/** @var PrisonPlayer $player */
					$player->bleed($e->getDamager(), mt_rand(30, 60) * $level);
				}
			},

			#region DISABLED ENCHANTMENTS
			self::STARVATION => function(EntityDamageByEntityEvent $e, int $level){
				$chance = mt_rand(1, 100) <= $level * 5;
				if($chance){
					$en = $e->getEntity();
					if($en instanceof Player && $en->getHungerManager()->getFood() > 0){
						$en->getHungerManager()->setFood($en->getHungerManager()->getFood() - 1);
					}
				}
			},
			self::ELECTRIFY => function(EntityDamageByEntityEvent $e, int $level){
				$chance = mt_rand(1, 100) <= ($level == 1 ? 4 : 8);
				$entity = $e->getEntity();
				if ($chance && $entity instanceof Living) {
					$entity->getEffects()->add(new EffectInstance(VanillaEffects::SLOWNESS(), ($level == 1 ? 1 : 2) * 20, $level == 1 ? 3 : 4));
					$this->strikeLightning($e->getEntity()->getPosition());
				}
			},
			self::PIERCE => function(EntityDamageByEntityEvent $e, int $level){
				$chance = mt_rand(1, 100) <= ($level == 1 ? 3 : ($level == 2 ? 5 : 9));
				if($chance){
					$e->getEntity()->getWorld()->addSound($e->getEntity()->getPosition(), new AnvilFallSound());
					$e->setBaseDamage($e->getBaseDamage() * ($level == 1 ? 1.1 : 1.2));
				}
			},
			self::DECAY => function(EntityDamageByEntityEvent $e, int $level){
				$chance = mt_rand(1, 100) <= $level * 4;
				$entity = $e->getEntity();
				if ($chance && $entity instanceof Living) {
					$entity->getEffects()->add(new EffectInstance(VanillaEffects::WITHER(), 10 * 20, 1));
				}
			},
			self::COMBO => function(EntityDamageByEntityEvent $e, int $level){
				$killer = $e->getDamager();
				if (!($killer instanceof PrisonPlayer)) return;
				if(($c = $killer->getCombo()) % 3 == 0){
					$d = max(3, floor($c / 3) / 10);
					$e->setBaseDamage($e->getBaseDamage() * (1 + $d));
				}
			},
			self::TIDES => function(EntityDamageByEntityEvent $e, int $level){
				$chance = mt_rand(1, 100) <= $level * 6;
				if($chance){
					$e->getEntity()->getWorld()->addSound($e->getEntity()->getPosition(), new PlaySound($e->getEntity()->getPosition(), "random.splash"));
					for($i = 0; $i < mt_rand(15, 20); $i++){
						$e->getEntity()->getWorld()->addParticle($e->getEntity()->getPosition()->add(mt_rand(-10, 10) / 10, 0, mt_rand(-10, 10) / 10), new SplashParticle());
					}
					$e->setKnockback($e->getKnockback() * (1 + ($level / 4)));
					$e->setBaseDamage($e->getBaseDamage() + 1);
				}
			},

			self::TRIPLE_THREAT => function(EntityShootBowEvent $e, int $level){
				$player = $e->getEntity();
				$arrow = $e->getProjectile();
				$force = $e->getForce();

				$yaw = $arrow->getLocation()->getYaw();

				$yadd = 20;
				for($i = 1; $i <= 2; $i++){
					if($i == 1){ $ya = -$yadd; }else{ $ya = $yadd; }
					$y = -sin(deg2rad($player->getLocation()->pitch));
					$xz = cos(deg2rad($player->getLocation()->pitch));

					$arrow = new PerishArrow(new Location(-$xz * sin(deg2rad($player->getLocation()->yaw + $ya)),
						$y,
						$xz * cos(deg2rad($player->getLocation()->yaw + $ya)),
						$player->getLocation()->getWorld(),
						($player->getLocation()->yaw > 180 ? 360 : 0) - $player->getLocation()->yaw + $ya,
						-$player->getLocation()->yaw
					), $player, $force == 2);
					$arrow->setMotion($arrow->getMotion()->multiply($force));
					$arrow->spawnToAll();
				}
			},
			self::RELOCATE => function(EntityShootBowEvent $e, int $level){
				$proj = $e->getProjectile();
				$e->setProjectile(new EnderArrow(new Location($proj->getPosition()->x, $proj->getPosition()->y, $proj->getPosition()->z, $proj->getLocation()->getWorld(), $proj->getLocation()->yaw, $proj->getLocation()->pitch, $proj->getWorld()), $e->getEntity(), false));
				$e->getProjectile()->setMotion($proj->getMotion());
				$e->setForce($e->getForce() * 0.5);
			},
			self::SNIPER => function(EntityShootBowEvent $e, int $level){
				/** @var Arrow */
				$proj = $e->getProjectile();
				$e->setProjectile(new SniperArrow(new Location($proj->getPosition()->x, $proj->getPosition()->y, $proj->getPosition()->z, $proj->getLocation()->getWorld(), $proj->getLocation()->yaw, $proj->getLocation()->pitch, $proj->getWorld()), $e->getEntity(), $proj->isCritical()));
				$e->getProjectile()->setMotion($proj->getMotion());
			},
			#endregion

			self::EXPLOSIVE => function(BlockBreakEvent $e, int $level){
				/** @var PrisonPlayer $player */
				$player = $e->getPlayer();
				$block = $e->getBlock();
				$lvl = $block->getPosition()->getWorld();
				$session = $player->getGameSession()->getMines();
				$chance = mt_rand(1, 100) <= $level * 6;
				if($chance && $session->inMine()){
					$mine = $session->getMine();
					$drops = $block->getDrops($e->getItem());
					$explo = false;
					$mined = 0;
					for($x = $block->getPosition()->getX() - mt_rand(0, min(4, round($level * 0.75) + 1)); $x <= $block->getPosition()->getX() + mt_rand(0, min(4, round($level * 0.75) + 1)); $x++){
						for($y = $block->getPosition()->getY() - mt_rand(0, min(4, round($level * 0.75) + 1)); $y <= $block->getPosition()->getY() + mt_rand(0, min(4, round($level * 0.75) + 1)); $y++){
							for($z = $block->getPosition()->getZ() - mt_rand(0, min(4, round($level * 0.75) + 1)); $z <= $block->getPosition()->getZ() + mt_rand(0, min(4, round($level * 0.75) + 1)); $z++){
								if(mt_rand(0, 2) == 1){
									$explo = true;
									$pos = new Vector3($x, $y, $z);
									$b = $lvl->getBlock($pos);
									if($mine->inMine($b->getPosition())){
										foreach($b->getDrops($e->getItem()) as $drop){
											$drops[] = $drop;
										}
										$lvl->setBlock($b->getPosition(), VanillaBlocks::AIR());
										$mined++;
									}
								}
							}
						}
					}
					$mine->addTotalMined($mined);
					if($explo){
						$this->explosion($block);
						$e->setDrops($drops);
					}
				}
			},
			self::ORE_MAGNET => function(BlockBreakEvent $e, int $level){
				switch($level){
					default:
					case 1:
						$chance = mt_rand(1, 100) <= 10;
						$add = [1, 3];
						break;
					case 2:
						$chance = mt_rand(1, 100) <= 25;
						$add = [2, 5];
						break;
					case 3:
						$chance = mt_rand(1, 100) <= 40;
						$add = [3, 7];
						break;
				}
				/** @var PrisonPlayer */
				$player = $e->getPlayer();
				$session = $player->getGameSession()->getMines();

				if($chance && $session->inMine()){
					$drops = $e->getDrops();
					foreach($drops as $key => $drop){
						$drop->setCount($drop->getCount() + mt_rand($add[0], $add[1]));
					}
					$e->setDrops($drops);
				}
			},
			self::FEED => function(BlockBreakEvent $e, int $level) {
				/** @var PrisonPlayer */
				$player = $e->getPlayer();
				$block = $e->getBlock();
				$lvl = $block->getPosition()->getWorld();
				$session = $player->getGameSession()->getMines();

				$chance = mt_rand(1, 5) == 1;
				if($chance && $session->inMine()){
					$player->getWorld()->addSound($player->getPosition(), new GenericSound($player->getPosition(), LevelSoundEvent::BURP));
					$player->getHungerManager()->setFood(min($player->getHungerManager()->getFood() + 2, 20));
					$player->getHungerManager()->setSaturation(min($player->getHungerManager()->getSaturation() + 2, 20));
				}
			},
			self::TRANSFUSION => function(BlockBreakEvent $e, int $level) {
				/** @var PrisonPlayer */
				$player = $e->getPlayer();
				$block = $e->getBlock();
				$lvl = $block->getPosition()->getWorld();
				$session = $player->getGameSession()->getMines();

				switch($level){
					default:
					case 1:
						$chance = mt_rand(1, 100) <= 6;
						break;
					case 2:
						$chance = mt_rand(1, 100) <= 12;
						break;
					case 3:
						$chance = mt_rand(1, 100) <= 15;
						break;
					case 4:
						$chance = mt_rand(1, 100) <= 18;
						break;
					case 5:
						$chance = mt_rand(1, 100) <= 20;
						break;
				}
				if($chance && $session->inMine()){
					$convert = [
						ItemTypeIds::COAL => ItemTypeIds::IRON_INGOT,
						ItemTypeIds::IRON_INGOT => ItemTypeIds::GOLD_INGOT,
						ItemTypeIds::GOLD_INGOT => ItemTypeIds::REDSTONE_DUST,
						ItemTypeIds::REDSTONE_DUST => ItemTypeIds::DIAMOND,
						ItemTypeIds::DIAMOND => ItemTypeIds::EMERALD,
						ItemTypeIds::EMERALD => VanillaBlocks::OBSIDIAN()->asItem()->getTypeId(),
						VanillaBlocks::COAL()->asItem()->getTypeId() => VanillaBlocks::IRON()->asItem()->getTypeId(),
						VanillaBlocks::IRON()->asItem()->getTypeId() => VanillaBlocks::GOLD()->asItem()->getTypeId(),
						VanillaBlocks::GOLD()->asItem()->getTypeId() => VanillaBlocks::REDSTONE()->asItem()->getTypeId(),
						VanillaBlocks::REDSTONE()->asItem()->getTypeId() => VanillaBlocks::DIAMOND()->asItem()->getTypeId(),
						VanillaBlocks::DIAMOND()->asItem()->getTypeId() => VanillaBlocks::EMERALD()->asItem()->getTypeId()
					];
					$converted = false;
					$drops = $e->getDrops();
					foreach($drops as $key => $drop){
						$id = $drop->getTypeId();
						if(isset($convert[$id])){
							$drops[$key] = ItemRegistry::getItemById($convert[$id], -1, $drop->getCount());
							$converted = true;
						}
					}
					if($converted){
						$e->setDrops($drops);
					}
				}
			},
			self::KEYPLUS => function(BlockBreakEvent $e, int $level){
				$block = $e->getBlock();
				/** @var PrisonPlayer */
				$player = $e->getPlayer();
				$mines = Prison::getInstance()->getMines();
				$session = $player->getGameSession()->getMines();

				$nokey = [1, 3, 12, 14, 15, 17];
				if(!isset($nokey[LegacyBlockIds::typeIdToLegacyId($block->getTypeId())]) && $session->inMine()){
					$session = $player->getGameSession()->getMysteryBoxes();
					if (mt_rand(0, 60) == 1) $session->addKeysWithPopup("iron", 1, "mob.chicken.hurt");
					if (mt_rand(0, 115) == 1) $session->addKeysWithPopup("gold", 1, "mob.chicken.hurt");
					if (mt_rand(0, 180) == 1) $session->addKeysWithPopup("diamond", 1, "mob.chicken.hurt");
					if (mt_rand(0, 300) == 1) $session->addKeysWithPopup("emerald", 1, "mob.chicken.hurt");
				}
			},
			self::KEY_RUSH => function(BlockBreakEvent $e, int $level){
				/** @var PrisonPlayer $player */
				$player = $e->getPlayer();
				$session = $player->getGameSession()->getMines();
				if(!$session->inMine()) return;
				
				switch(true){
					case $session->getStreak() % 300 === 0:
						$keys = 3;
						break;
					case $session->getStreak() % 200 === 0:
						$keys = 2;
						break;
					case $session->getStreak() % 100 === 0:
						$keys = 1;
						break;
					default:
						$keys = 0;
				}

				if($keys > 0){

					$session = $player->getGameSession()->getMysteryBoxes();
					$keyTypes = array_keys($session->keys);
					unset($keyTypes[array_search("divine", $keyTypes)]);

					$session->addKeysWithPopup(
						$keyTypes[array_rand($keyTypes)],
						$keys,
						"mob.chicken.hurt"
					);
				}
			},
			self::EXCAVATE => function(BlockBreakEvent $e, int $level){
				$block = $e->getBlock();
				$item = $e->getItem();
				/** @var PrisonPlayer */
				$player = $e->getPlayer();
				$chance = mt_rand(1, 100) <= $level * 4;
				if($chance && $player->inPlotWorld()){
					$explosion = new Explosion($block->getPosition(), 2, $player);
					$explosion->explodeA();
					$explosion->explodeB();
				}
			},


			self::CHARM => function(BlockBreakEvent $e, int $level){
				$block = $e->getBlock();
				/** @var PrisonPlayer */
				$player = $e->getPlayer();

				$chance = mt_rand(1, 100) <= ($level == 1 ? 5 : 10);
				if($chance && !$player->inPlotWorld()){
					$charmItem = $this->getRandomCharmItem($level);
					$giveItem = true;

					if($charmItem instanceof TechitNote){
						// Can't do getInventory()->first() & get item from slot because all techit notes have different ids
						foreach($player->getInventory()->getContents(true) as $slot => $item){
							if(!$item instanceof TechitNote) continue;
							if(!$item->getCreatedBy() === "CHARM " . Prison::getInstance()->getEnchantments()->getRoman($level)) continue;

							$item->setup("CHARM " . Prison::getInstance()->getEnchantments()->getRoman($level), $item->getTechits() + (1500 * $level));
							$player->getInventory()->setItem($slot, $item);
							$giveItem = false;
							break;
						}
					}

					if($giveItem){
						$player->getInventory()->addItem($charmItem);
					}

					$player->getWorld()->addSound($player->getPosition(), new GenericSound($player->getPosition(), 20));
				}
			},
			self::MOMENTUM => function(BlockBreakEvent $e, int $level){
				$block = $e->getBlock();
				/** @var PrisonPlayer */
				$player = $e->getPlayer();
				$mines = Prison::getInstance()->getMines();
				$session = $player->getGameSession()->getMines();

				$add = false;
				if($session->getStreak() % ($level == 1 ? 90 : 80) == 0 && $session->inMine() && !Prison::getInstance()->getBlockTournament()->getGameManager()->inGame($player)){
					if($player->getEffects()->has(VanillaEffects::HASTE())){
						$effect = $player->getEffects()->get(VanillaEffects::HASTE());
						if($effect->getDuration() < 20 * 60 * 5){
							$effect = $effect->setDuration(min(20 * 60 * 30, $effect->getDuration() + (20 * ($level == 1 ? mt_rand(10, 15) : mt_rand(10, 20)))));
							$add = true;
						}
					}else{
						$effect = new EffectInstance(VanillaEffects::HASTE(), 20 * ($level == 1 ? mt_rand(10, 15) : mt_rand(10, 20)), $level);
						$add = true;
					}
					if($add){
						$player->getEffects()->remove(VanillaEffects::HASTE());
						$player->getEffects()->add($effect);
					}else{
						$player->sendTip(TextFormat::RED . "Max haste achieved!");
					}

					$player->getWorld()->addSound($player->getPosition(), new GenericSound($player->getPosition(), 56));
				}
			},
			self::AIRSTRIKE => function(BlockBreakEvent $e, int $level){
				$block = $e->getBlock();
				/** @var PrisonPlayer */
				$player = $e->getPlayer();
				$mines = Prison::getInstance()->getMines();
				$session = $player->getGameSession()->getMines();
				$mine = $session->getMine();
				if($mine === null) return;

				$chance = mt_rand(1, 100) <= ($level * 3);
				if($chance){
					$player->getWorld()->addSound($player->getPosition(), new GenericSound($player->getPosition(), 64));
					$player->getWorld()->addSound($player->getPosition(), new GenericSound($player->getPosition(), 134));
					$this->strikeLightning($block->getPosition());

					$mined = 0;
					/** @var Item[] */
					$drops = [];
					for($y = $mine->getFirstCorner()->y; $y <= $mine->getSecondCorner()->y; $y++){
						$b = $mine->getWorld()->getBlockAt($block->getPosition()->x, $y, $block->getPosition()->z);
						if($b->getTypeId() !== LegacyBlockIds::legacyIdToTypeId(0) && $mine->inMine($b->getPosition())){
							foreach($b->getDrops($e->getItem()) as $drop){
								$drops[] = $drop;
							}
							$b->getPosition()->getWorld()->setBlock($b->getPosition(), VanillaBlocks::AIR());
							$mined++;
						}
					}
					$mine->addTotalMined($mined);
					$d = $e->getDrops();
					foreach($drops as $drop){
						$in = false;
						foreach($d as $key => $dr){
							if($dr->getTypeId() == $drop->getTypeId()){
								$dr->setCount($dr->getCount() + $drop->getCount());
								$in = true;
							}
						}
						if(!$in) $d[] = $drop;
					}
					$e->setDrops($d);
				}
			},
			self::IMPLODE => function(BlockBreakEvent $e, int $level){
				$block = $e->getBlock();
				/** @var PrisonPlayer */
				$player = $e->getPlayer();
				$session = $player->getGameSession()->getMines();

				$m = 0;
				$p = 0;
				switch(true){
					case $session->getStreak() % 320 == 0:
						$m = -2;
						$p = 2;
						break;
					case $session->getStreak() % 240 == 0:
						$m = -1;
						$p = 2;
						break;
					case $session->getStreak() % 160 == 0:
						$m = -1;
						$p = 1;
						break;
					case $session->getStreak() % 80 == 0:
						$m = 0;
						$p = 1;
						break;
				}
				if($m != 0 || $p != 0){
					$mine = $session->getMine();
					if($mine === null) return;

					$player->getWorld()->addSound($player->getPosition(), new PlaySound($player->getPosition(), "mob.wither.break_block"));

					$mined = 0;
					/** @var Item[] */
					$drops = [];
					for($x = $m; $x <= $p; $x++){
						for($y = $m; $y <= $p; $y++){
							for($z = $m; $z <= $p; $z++){
								$b = $block->getPosition()->getWorld()->getBlockAt($block->getPosition()->getX() + $x, $block->getPosition()->getY() + $y, $block->getPosition()->getZ() + $z);
								if($b->getTypeId() !== LegacyBlockIds::legacyIdToTypeId(0) && $mine->inMine($b->getPosition())){
									foreach($b->getDrops($e->getItem()) as $drop){
										$drops[] = $drop;
									}
									$b->getPosition()->getWorld()->setBlock($b->getPosition(), VanillaBlocks::AIR());
									$mined++;
								}
							}
						}
					}
					$mine->addTotalMined($mined);

					$d = $e->getDrops();
					foreach($drops as $drop){
						$in = false;
						foreach($d as $key => $dr){
							if($dr->getTypeId() == $drop->getTypeId()){
								$dr->setCount($dr->getCount() + $drop->getCount());
								$in = true;
							}
						}
						if(!$in) $d[] = $drop;
					}
					$e->setDrops($d);
				}
			},

			self::WORM => function(BlockBreakEvent $e, int $level){
				$block = $e->getBlock();
				/** @var PrisonPlayer */
				$player = $e->getPlayer();
				$mines = Prison::getInstance()->getMines();
				$session = $player->getGameSession()->getMines();
				$mine = $session->getMine();
				if($mine === null) return;

				$chance = mt_rand(1, 100) <= ($level * 3);
				if($chance){
					$player->getWorld()->addSound($player->getPosition(), new GenericSound($player->getPosition(), 64));
					$player->getWorld()->addSound($player->getPosition(), new GenericSound($player->getPosition(), 134));

					$mined = 0;
					/** @var Item[] */
					$drops = [];

					switch(mt_rand(1, 5)){
						case 1:
							for($x = $mine->getFirstCorner()->x; $x <= $mine->getSecondCorner()->x; $x++){
								$b = $mine->getWorld()->getBlockAt($x, $block->getPosition()->y, $block->getPosition()->z);
								if($b->getTypeId() !== LegacyBlockIds::legacyIdToTypeId(0) && $mine->inMine($b->getPosition())){
									foreach($b->getDrops($e->getItem()) as $drop){
										$drops[] = $drop;
									}
									$b->getPosition()->getWorld()->setBlock($b->getPosition(), VanillaBlocks::AIR());
									$b->getPosition()->getWorld()->addParticle($b->getPosition(), new BlockBreakParticle($b));
									$mined++;
								}
							}
							if($level >= 3){
								for($z = $mine->getFirstCorner()->z; $z <= $mine->getSecondCorner()->z; $z++){
									$b = $mine->getWorld()->getBlockAt($block->getPosition()->x, $block->getPosition()->y, $z);
									if($b->getTypeId() !== LegacyBlockIds::legacyIdToTypeId(0) && $mine->inMine($b->getPosition())){
										foreach($b->getDrops($e->getItem()) as $drop){
											$drops[] = $drop;
										}
										$b->getPosition()->getWorld()->setBlock($b->getPosition(), VanillaBlocks::AIR());
										$b->getPosition()->getWorld()->addParticle($b->getPosition(), new BlockBreakParticle($b));
										$mined++;
									}
								}
							}
							break;
						case 2:
						case 3:
							for($x = $mine->getFirstCorner()->x; $x <= $mine->getSecondCorner()->x; $x++){
								$b = $mine->getWorld()->getBlockAt($x, $block->getPosition()->y, $block->getPosition()->z);
								if($b->getTypeId() !== LegacyBlockIds::legacyIdToTypeId(0) && $mine->inMine($b->getPosition())){
									foreach($b->getDrops($e->getItem()) as $drop){
										$drops[] = $drop;
									}
									$b->getPosition()->getWorld()->setBlock($b->getPosition(), VanillaBlocks::AIR());
									$b->getPosition()->getWorld()->addParticle($b->getPosition(), new BlockBreakParticle($b));
									$mined++;
								}
							}
							break;
						case 4:
						case 5:
							for($z = $mine->getFirstCorner()->z; $z <= $mine->getSecondCorner()->z; $z++){
								$b = $mine->getWorld()->getBlockAt($block->getPosition()->x, $block->getPosition()->y, $z);
								if($b->getTypeId() !== LegacyBlockIds::legacyIdToTypeId(0) && $mine->inMine($b->getPosition())){
									foreach($b->getDrops($e->getItem()) as $drop){
										$drops[] = $drop;
									}
									$b->getPosition()->getWorld()->setBlock($b->getPosition(), VanillaBlocks::AIR());
									$b->getPosition()->getWorld()->addParticle($b->getPosition(), new BlockBreakParticle($b));
									$mined++;
								}
							}
							break;
					}
					$mine->addTotalMined($mined);
					$d = $e->getDrops();
					foreach($drops as $drop){
						$in = false;
						foreach($d as $key => $dr){
							if($dr->getTypeId() == $drop->getTypeId()){
								$dr->setCount($dr->getCount() + $drop->getCount());
								$in = true;
							}
						}
						if(!$in) $d[] = $drop;
					}
					$e->setDrops($d);
				}
			},


			self::CROUCH => function(EntityDamageByEntityEvent $e, int $level){
				$player = $e->getEntity();

				if(!$player instanceof Player || !$player->isSneaking()) return;

				switch ($level) {
					default:
					case 1:
						$chance = mt_rand(1, 20) == 1;
						break;
					case 2:
						$chance = mt_rand(1, 15) == 1;
						break;
					case 3:
						$chance = mt_rand(1, 10) == 1;
						break;
					case 4:
						$chance = mt_rand(1, 5) == 1;
						break;
				}

				if($chance){
					$e->setBaseDamage($e->getBaseDamage() / (($level / 2) + 1));
				}
			},
			self::SCORCH => function(EntityDamageByEntityEvent $e, int $level){
				$killer = $e->getDamager();
				switch ($level) {
					default:
					case 1:
						$chance = mt_rand(1, 45) == 1;
						break;
					case 2:
						$chance = mt_rand(1, 30) == 1;
						break;
					case 3:
						$chance = mt_rand(1, 20) == 1;
						break;
					case 4:
						$chance = mt_rand(1, 15) == 1;
						break;
					case 4:
						$chance = mt_rand(1, 10) == 1;
						break;
				}

				if($chance){
					$killer->setOnFire(mt_rand(2, $level + 2));
				}
				$e->getEntity()->extinguish();
			},
			self::THORNS => function(EntityDamageByEntityEvent $e, int $level){
				switch($level){
					default:
					case 1:
						$chance = mt_rand(1,20) == 1;
						break;
					case 2:
						$chance = mt_rand(1,15) == 1;
						break;
					case 3:
						$chance = mt_rand(1,10) == 1;
						break;
					case 4:
						$chance = mt_rand(1,5) == 1;
						break;
				}

				$killer = $e->getDamager();
				if($chance){
					//$this->hitAs($e->getEntity(), $killer, ($level / 2) + 1);
				}
			},
			self::SHOCKWAVE => function(EntityDamageByEntityEvent $e, int $level){
				switch($level){
					default:
					case 1:
						$chance = mt_rand(1, 100) <= 8;
						break;
					case 2:
						$chance = mt_rand(1, 100) <= 15;
						break;
				}

				$killer = $e->getDamager();
				if($chance){
					$this->strikeLightning($killer->getPosition());
					foreach($killer->getViewers() as $viewer){
						if($viewer->getPosition()->distance($killer->getPosition()) < 6){
							$this->repel($viewer, $killer);
						}
					}
				}
			},
			self::ADRENALINE => function(EntityDamageByEntityEvent $e, int $level){
				$entity = $e->getEntity();
				if (!$entity instanceof Living) return;
				if($entity->getHealth() - $e->getBaseDamage() <= 5){
					$entity->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 10 * 20, 1));
				}
			},

			#region DISABLED ENCHANTMENTS
			self::SNARE => function(EntityDamageByEntityEvent $e, int $level){
				$killer = $e->getDamager();
				$chance = mt_rand(1, 100) <= 20;
				if($chance && $e->getEntity() instanceof Player){
					$this->drag($e->getEntity(), $killer);
					$killer->getWorld()->addSound($killer->getPosition(), new LaunchSound(), $killer->getViewers());
				}

			},
			self::RAGE => function(EntityDamageByEntityEvent $e, int $level) {
				$entity = $e->getEntity();
				if (!$entity instanceof Living) return;
				$chance = mt_rand(1, 100) <= ($level * 5);
				if($chance){
					$entity->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(), 20 * ($level * mt_rand(1, 2))));
					$entity->getEffects()->add(new EffectInstance(VanillaEffects::RESISTANCE(), 20 * ($level * mt_rand(1, 2))));
				}
			},
			self::SORCERY => function(EntityDamageByEntityEvent $e, int $level){
				$killer = $e->getDamager();
				if (!$killer instanceof Living) return;
				$chance = mt_rand(1, 100) <= ($level == 1 ? 4 : ($level == 2 ? 9 : 12));
				if($chance){
					$bad = [
						VanillaEffects::SLOWNESS(),
						VanillaEffects::MINING_FATIGUE(),
						VanillaEffects::NAUSEA(),
						VanillaEffects::BLINDNESS(),
						VanillaEffects::HUNGER(),
						VanillaEffects::WEAKNESS(),
						VanillaEffects::POISON(),
						VanillaEffects::FATAL_POISON(),
						VanillaEffects::WITHER(),
					];
					$effect = new EffectInstance($bad[array_rand($bad)], 20 * ($level * 4));
					$killer->getEffects()->add($effect);
					$e->getEntity()->getWorld()->addSound($e->getEntity()->getPosition(), new PlaySound($e->getEntity()->getPosition(), "mob.evocation_illager.cast_spell"));
				}
			},
			self::BLESSING => function(EntityDamageByEntityEvent $e, int $level){
				$killer = $e->getDamager();
				$entity = $e->getEntity();
				if (!$killer instanceof Living || !$entity instanceof Living) return;
				$chance = mt_rand(1, 100) <= ($level == 1 ? 3 : ($level == 2 ? 6 : 9));
				if($chance){
					$bad = [
						EffectIdMap::getInstance()->toId(VanillaEffects::SLOWNESS()),
						EffectIdMap::getInstance()->toId(VanillaEffects::MINING_FATIGUE()),
						EffectIdMap::getInstance()->toId(VanillaEffects::NAUSEA()),
						EffectIdMap::getInstance()->toId(VanillaEffects::BLINDNESS()),
						EffectIdMap::getInstance()->toId(VanillaEffects::HUNGER()),
						EffectIdMap::getInstance()->toId(VanillaEffects::WEAKNESS()),
						EffectIdMap::getInstance()->toId(VanillaEffects::POISON()),
						EffectIdMap::getInstance()->toId(VanillaEffects::FATAL_POISON()),
						EffectIdMap::getInstance()->toId(VanillaEffects::WITHER()),
					];
					foreach($killer->getEffects()->all() as $effect){
						if(in_array(EffectIdMap::getInstance()->toId($effect->getType()), $bad)){
							$entity->getEffects()->remove($effect->getType());
							$killer->getEffects()->add($effect);
						}
					}
				}
			},
			self::DODGE => function(EntityDamageByEntityEvent $e, int $level){
				$chance = mt_rand(1, 100) <= ($level == 1 ? 5 : 10);
				if($chance){
					($pl = $e->getEntity())->getWorld()->addSound($pl->getPosition(), new PlaySound($pl->getPosition(), "mob.wither.hurt"));
					$e->cancel();
				}
			},
			self::GODLY_RETRIBUTION => function(EntityDamageByEntityEvent $e, int $level){
				if(($pl = $e->getEntity())->getHealth() - $e->getBaseDamage() <= 5 && $pl instanceof Living && !$pl->getEffects()->has(VanillaEffects::STRENGTH())){
					$pl->getWorld()->addSound($pl->getPosition(), new PlaySound($pl->getPosition(), "mob.wither.ambient"));
					$pl->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(), 10 * 20));
					$pl->getEffects()->add(new EffectInstance(VanillaEffects::REGENERATION(), 10 * 20, 1));
				}
			},
			#endregion

		];

		/* Armor specific */
		$this->equip = [
			self::OVERLORD => function(Player $player, $beforelevel, $afterlevel){
				$player->setMaxHealth(20 + ($afterlevel * 2));

				if($player->getHealth() >= 20 + ($beforelevel * 2)){
					$player->setHealth($player->getMaxHealth());
				}
			},

			self::GLOWING => function(Player $player, $beforelevel, $afterlevel){
				$player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 20 * 99999, 0, false));
			},

			self::GEARS => function(Player $player, $beforelevel, $afterlevel){
				$player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 20 * 99999, $afterlevel - 1, false));
			},
			self::BUNNY => function(Player $player, $beforelevel, $afterlevel){
				$player->getEffects()->add(new EffectInstance(VanillaEffects::JUMP_BOOST(), 20 * 99999, $afterlevel - 1, false));
			},
		];

		$this->unequip = [
			self::OVERLORD => function(Player $player, $beforelevel, $afterlevel){
				$player->setMaxHealth(20 + ($afterlevel * 2));
			},

			self::GLOWING => function(Player $player, $beforelevel, $afterlevel){
				$player->getEffects()->remove(VanillaEffects::NIGHT_VISION());
			},

			self::GEARS => function(Player $player, $beforelevel, $afterlevel){
				$player->getEffects()->remove(VanillaEffects::SPEED());
			},
			self::BUNNY => function(Player $player, $beforelevel, $afterlevel){
				$player->getEffects()->remove(VanillaEffects::JUMP_BOOST());
			},
		];

		$this->task = [
			self::GLOWING => function(Player $player, $currentTick, $level){
				if(!$player->getEffects()->has(VanillaEffects::NIGHT_VISION())) $player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 20 * 99999, 0, false));
			},

			self::SCORCH => function(Player $player, $currentTick, $level){
				if($player->isOnFire()) $player->extinguish();
			},
			self::GEARS => function(Player $player, $currentTick, $level){
				if(!$player->getEffects()->has(VanillaEffects::SPEED())) $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 20 * 99999, $level - 1, false));
			},
			self::BUNNY => function(Player $player, $currentTick, $level){
				if(!$player->getEffects()->has(VanillaEffects::JUMP_BOOST())) $player->getEffects()->add(new EffectInstance(VanillaEffects::JUMP_BOOST(), 20 * 99999, $level - 1, false));
			},
		];
	}

	public function strikeLightning(Position $pos) : void{
		$pos->getWorld()->addSound($pos, new PlaySound($pos, "ambient.weather.lightning.impact"));
		$pk = new AddActorPacket();
		$pk->type = "minecraft:lightning_bolt";
		$pk->actorRuntimeId = $pk->actorUniqueId = $eid = Entity::nextRuntimeId();
		$pk->position = $pos->asVector3();
		$pk->yaw = $pk->pitch = 0;
		$pk->syncedProperties = new PropertySyncData([], []);

		$p2d = [];
		foreach($pos->getWorld()->getPlayers() as $p){
			/** @var PrisonPlayer $p */
			if($p->isLoaded() && $p->getGameSession()->getSettings()->getSetting(PrisonSettings::LIGHTNING)){
				$p->getNetworkSession()->sendDataPacket($pk);
				$p2d[] = $p;
			}
		}
		Prison::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($p2d, $eid) : void{
			$pk = new RemoveActorPacket();
			$pk->actorUniqueId = $eid;
			foreach($p2d as $p) if($p->isConnected()) $p->getNetworkSession()->sendDataPacket($pk);
		}), 20);
	}

	public function explosion(Entity|Block $entity) {
		$pos = $entity->getPosition();
		$pos->getWorld()->addParticle($pos, new ExplodeParticle());
		if ($entity instanceof Entity) {
			$entity->broadcastSound(new ExplodeSound);
		} elseif ($entity instanceof Block) {
			$pos->getWorld()->addSound($pos, new ExplodeSound);
		}
	}

	public function drag(Player $to, Entity $from) : void{
		if (!$from instanceof Living) return;
		$t = $from->getPosition()->asVector3();
		$dv = $to->getPosition()->asVector3()->subtract($t->x, $t->y, $t->z)->normalize();
		$from->knockBack($dv->x, $dv->z, 0.45);
	}

	public function repel(Player $to, Entity $from) : void{
		$t = $to->getPosition()->asVector3();
		$dv = $from->getPosition()->asVector3()->subtract($t->x, $t->y, $t->z)->normalize();
		$to->knockback($dv->x, $dv->z, 0.8);
	}

	public function getRandomKeyType(Player $player, array $takingalready = [], int $tries = 0){
		/** @var PrisonPlayer $player */
		if($tries >= 10) return false;
		$type = ["iron", "gold", "diamond", "emerald", "vote"][mt_rand(0, 4)];
		$amt = $player->getGameSession()->getMysteryBoxes()->getKeys($type);
		if(($amt - $takingalready[$type]) <= 0 && $tries < 10){
			$tries++;
			$type = $this->getRandomKeyType($player, $takingalready, $tries);
		}
		return $type;
	}

	public function getRandomCharmItem(int $level = 1) : Item{
		$items = [
			VanillaItems::GOLDEN_APPLE()->setCount(mt_rand(1, $level == 1 ? mt_rand(2, 3) : mt_rand(2, 6))),
			VanillaItems::EXPERIENCE_BOTTLE()->setCount(mt_rand(1, $level == 1 ? mt_rand(3, 8) : mt_rand(3, 16))),
		];
		$mn = ItemRegistry::MINE_NUKE();
		$mn->init();
		$n = ItemRegistry::NAMETAG();
		$n->init();
		$cdt = ItemRegistry::CUSTOM_DEATH_TAG();
		$cdt->init();
		$tn = ItemRegistry::TECHIT_NOTE();
		$tn->setup("CHARM " . Prison::getInstance()->getEnchantments()->getRoman($level), 1500 * $level);
		$rare = [
			$mn,
			$n,
			$cdt,
			$tn,
			VanillaItems::ENCHANTED_GOLDEN_APPLE()->setCount(mt_rand(1, $level == 1 ? 2 : mt_rand(1, 2))),
		];
		if($level == 2){
			if(mt_rand(1, 100) <= 55){
				foreach($rare as $i)
					$items[] = $i;
			}
		}

		return $items[array_rand($items)];
	}

	public function hitAs(Entity $killer, Entity $hit, float $damage){
		$hit->attack(new EntityDamageByEntityEvent($killer, $hit, 1, $damage, [], 0.4));
	}

}