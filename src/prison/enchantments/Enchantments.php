<?php namespace prison\enchantments;

use core\items\type\TieredTool;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\event\{
	block\BlockBreakEvent,
    Cancellable,
    entity\EntityDamageByEntityEvent,
	entity\EntityShootBowEvent,
    Event,
};
use pocketmine\item\{
	Item,
	Axe,
	Pickaxe,
	Tool
};
use pocketmine\item\enchantment\{
	EnchantmentInstance,
};
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\world\World;

use prison\Prison;
use prison\enchantments\EnchantmentData as ED;
use prison\enchantments\commands\{
	AddEnchant,
    AddEssence,
    Blacksmith,
	Enchanter,
	Guide,
	GiveBook,
	AnimatorGuide,
    Conjuror,
    EditItem,
    EssenceGuide,
    MyEssence,
    PouchofEssence,
    Sign,
	Repair,
    SetEssence,
    Tree
};
use prison\enchantments\effects\Effects;
use prison\entity\{
	XpBottle,
	ArmorStand as EntityArmorStand,
};
use prison\enchantments\type\{
	Enchantment,
	ArmorEnchantment,
	UniversalEnchantment
};
use prison\gangs\battle\BattleKit;
use prison\PrisonPlayer;

use core\utils\TextFormat;
use core\vote\Structure as VS;
use pocketmine\block\BlockToolType;

class Enchantments{

	private static array $_registry = [];

	private static function _registryRegister(string $id, Enchantment $ench) {
		self::$_registry[$id] = $ench;
	}

	private static function _registryGet(string $id): ?Enchantment {
		if (!isset(self::$_registry[$id])) return null;

		return self::$_registry[$id];
	}

	public static function __callStatic($name, $arguments) {
		return self::_registryGet($name);
	}

	public Calls $calls;

	public Effects $effects;

	/** @var Enchantment[] */
	public static array $enchantments = [];
	public array $nukecd = [];
	public array $gncd = [];
	public array $hbcd = [];

	public array $acache = [];

	public array $r_cooldown = [];

	public array $pXpCache = [];

	public function __construct(public Prison $plugin){
		$this->calls = new Calls;
		$this->effects = new Effects($plugin, $this);

		$plugin->getServer()->getCommandMap()->registerAll("enchantments", [
			new AddEnchant($plugin, "addenchant", "Add an enchant to an item"),
			new Blacksmith($plugin, "blacksmith", "Open the Blacksmith menu"),
			new Conjuror($plugin, "conjuror", "Open the Conjuror menu"),
			new Enchanter($plugin, "enchanter", "Open the Enchanter menu"),
			new Guide($plugin, "guide", "Open the Enchantment guide"),
			new AnimatorGuide($plugin, "animatorguide", "Open the Animator guide"),
			new Sign($plugin, "sign", "Sign the item you're holding"),
			new Repair($plugin, "repair", "Instantly repair broken items (ranked)"),
			new GiveBook($plugin, "givebook", "Give book"),
			new EssenceGuide($plugin, "essenceguide", "Open the Essence Guide"),

			new SetEssence($plugin, "setessence", "Set player essence"),
			new AddEssence($plugin, "addessence", "Give player essence"),
			new MyEssence($plugin, "myessence", "Check your essence"),
			new PouchofEssence($plugin, "pouchofessence", "Create a Pouch of Essence"),
			//new Tree($plugin, "tree", "View skill tree progress of held item"),

			//new AnimatorTest($plugin, "animatortest", "Animator test (sn3ak only)"),
			new EditItem($plugin, "edititem", "Item editor (sn3ak only)"),
		]);

		EntityFactory::getInstance()->register(EntityArmorStand::class, function(World $world, CompoundTag $nbt) : EntityArmorStand{
			return new EntityArmorStand(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ["minecraft:armor_stand", "ArmorStand"]);
		EntityFactory::getInstance()->register(XpBottle::class, function(World $world, CompoundTag $nbt) : XpBottle{
			return new XpBottle(EntityDataHelper::parseLocation($nbt, $world), null);
		}, ["minecraft:xp_bottle"]);

		$this->setupEnchantments();
	}

	public function getEffects() : Effects{
		return $this->effects;
	}

	public function setupEnchantments() : void{
		foreach(ED::ENCHANTMENTS as $id => $data){
			$type = $data["type"];
			if(
				$type == ED::SLOT_ARMOR ||
				$type == ED::SLOT_HEAD ||
				$type == ED::SLOT_TORSO ||
				$type == ED::SLOT_LEGS ||
				$type == ED::SLOT_FEET
			){
				self::$enchantments[$id] = new ArmorEnchantment($id);
			}elseif($type == ED::SLOT_ALL){
				self::$enchantments[$id] = new UniversalEnchantment($id);
			}else{
				self::$enchantments[$id] = new Enchantment($id);
			}
			self::_registryRegister(str_replace(' ', '_', strtoupper(self::$enchantments[$id]->getName())), self::$enchantments[$id]);
		}
	}

	public function tick(int $currentTick) : void{
		foreach($this->acache as $name => $data){
			$player = $this->plugin->getServer()->getPlayerExact($name);
			if($player instanceof Player){
				foreach($data as $id => $level){
					if(isset($this->calls->task[$id])){
						$this->calls->task[$id]($player, $currentTick, $level);
					}
				}
			}else{
				unset($this->acache[$name]);
			}
		}
	}

	public function getEWE(EnchantmentInstance $enchantment) : ?Enchantment{
		return $this->getEnchantment(EnchantmentIdMap::getInstance()->toId($enchantment->getType()));
	}

	public function getEnchantment(int $id) : ?Enchantment{
		return self::$enchantments[$id] ?? null;
	}

	/** @return Enchantment[] */
	public function getEnchantments(int $rarity = -1, bool $showDisabled = false) : array{
		$enchantments = [];
		if($rarity == -1){
			if($showDisabled){
				return self::$enchantments;
			}
			foreach (self::$enchantments as $enchantment) {
				if(!$enchantment->isDisabled()){
					$enchantments[$enchantment->getRuntimeId()] = $enchantment;
				}
			}
		}else{
			foreach (self::$enchantments as $enchantment) {
				if((!$enchantment->isDisabled() || $showDisabled) && $enchantment->getRarity() === $rarity){
					$enchantments[$enchantment->getRuntimeId()] = $enchantment;
				}
			}
		}
		return $enchantments;
	}

	public function getEnchantmentByName(string $name, bool $isStaff = false): ?Enchantment {
		foreach (self::$enchantments as $ench) {
			if (strtolower($ench->getName()) == strtolower($name) && ($isStaff || !$ench->isDisabled())) return $ench;
		}
		return null;
	}

	public function getRandomEnchantment(int $rarity = 1) : Enchantment{
		$enchantments = $this->getEnchantments($rarity);
		return $enchantments[array_rand($enchantments)];
	}

	public function process(Event $event) : void{
		if(($event instanceof Cancellable && $event->isCancelled())) return;

		if($event instanceof EntityDamageByEntityEvent){
			$hurt = $event->getEntity();
			$killer = $event->getDamager();

			if (!$killer instanceof Player) return;
			/** @var PrisonPlayer $killer */

			$khand = $killer->getInventory()->getItemInHand();
			if($khand instanceof Tool){
				if(
					$khand->hasEnchantments() && (
						$khand->getNamedTag()->getInt(BattleKit::BATTLE_TAG, 0) != 1 ||
						$killer->isBattleParticipant()
					)
				){
					/** @var array<int,EnchantmentInstance> */
					$ordered = [
						Calls::WORM => -1,
						Calls::AIRSTRIKE => -1,
						Calls::IMPLODE => -1,
						Calls::EXPLOSIVE => -1,
						Calls::ORE_MAGNET => -1,
						Calls::TRANSFUSION => -1,
					];
					foreach($khand->getEnchantments() as $enchantment){
						$id = EnchantmentIdMap::getInstance()->toId($enchantment->getType());
						$level = $enchantment->getLevel();
						$khand->removeEnchantment(EnchantmentIdMap::getInstance()->fromId($id), $level);
						$khand->addEnchantment(($ench = $this->getEnchantment($id))->getEnchantmentInstance($level));
						$killer->getInventory()->setItemInHand($khand);

						if(
							$ench !== null &&
							$ench->isHandled() &&
							!$ench->isDisabled() &&
							$ench->getType()->get(ED::SLOT_SWORD)
						){
							if (isset($ordered[$ench->getId()])) $ordered[$ench->getId()] = $enchantment;
							elseif (isset($this->calls->event[$ench->getId()])) {
								$this->calls->event[$ench->getId()]($event, $enchantment->getLevel());
							}
						}
					}
					foreach ($ordered as $id => $ench) {
						if (is_numeric($ench) || !isset($this->calls->event[$id])) continue;
						$this->calls->event[$id]($event, $ench->getLevel());
					}
				}
			}

			if($hurt instanceof Player){
				$cache = $this->acache[$hurt->getName()];
				foreach($cache as $id => $level){
					if(isset($this->calls->event[$id])){
						$this->calls->event[$id]($event, $level);
					}
				}
			}

			if($event->getBaseDamage() >= 15) $event->setBaseDamage(15);
		}

		if($event instanceof BlockBreakEvent){
			/** @var PrisonPlayer $player */
			$player = $event->getPlayer();
			$session = $player->getGameSession()->getMines();
			$hand = $player->getInventory()->getItemInHand();
			if($hand instanceof Tool){
				if($hand->hasEnchantments()){
					/** @var array<int,EnchantmentInstance> */
					$ordered = [
						Calls::WORM => -1,
						Calls::AIRSTRIKE => -1,
						Calls::IMPLODE => -1,
						Calls::EXPLOSIVE => -1,
						Calls::ORE_MAGNET => -1,
						Calls::TRANSFUSION => -1,
					];
					foreach($hand->getEnchantments() as $enchantment){
						$id = EnchantmentIdMap::getInstance()->toId($enchantment->getType());
						
						$level = $enchantment->getLevel();
						$hand->removeEnchantment(EnchantmentIdMap::getInstance()->fromId($id), $level);
						$hand->addEnchantment(($ench = $this->getEnchantment($id))->getEnchantmentInstance($level));
						$player->getInventory()->setItemInHand($hand);
						
						if(
							$ench !== null && 
							$ench->isHandled() &&
							(!$ench->isDisabled() || $player->isStaff()) &&
							(
								$ench->getType()->get(ED::SLOT_DIG) ||
								$ench->getType()->get(ED::SLOT_AXE) ||
								$ench->getType()->get(ED::SLOT_PICKAXE) ||
								$ench->getType()->get(ED::SLOT_SHOVEL)
						)
						){
							if (isset($ordered[$ench->getId()])) $ordered[$ench->getId()] = $enchantment;
							elseif (isset($this->calls->event[$ench->getId()])) {
								$this->calls->event[$ench->getId()]($event, $enchantment->getLevel());
							}
						} 
					}

					foreach ($ordered as $id => $ench) {
						if (is_numeric($ench) || !isset($this->calls->event[$id])) continue;
						$this->calls->event[$id]($event, $ench->getLevel());
					}
				}

				if(TieredTool::isPickaxe($hand) && $session->inMine()){
					if(!isset($this->pXpCache[$player->getName()])){
						$this->pXpCache[$player->getName()] = 0;
					}
					$this->pXpCache[$player->getName()]++;
					$data = new ItemData($hand);
					$mined = $data->getBlocksMined();
					$send = true;
					$color = null;
					$xp = 0;
					$essence = 0;
					$max = 4;
					switch(true){
						case $mined == 0:
						break;
						case $mined % 75 == 0: // ESSENCE
							$essence = mt_rand(3, 5);
							$color = TextFormat::DARK_AQUA;
							break;
						case $mined % 1000 == 0: // XP
							$max = 2;
							$xp = mt_rand(90, 135);
							$color = TextFormat::AQUA;
							break;
						case $mined % 500 == 0: // XP
							$max = 3;
							$xp = mt_rand(45, 90);
							$color = TextFormat::GREEN;
							break;
						case $mined % 100 == 0: // XP
							$xp = mt_rand(20, 45);
							$color = TextFormat::YELLOW;
							break;
						case $mined % 50 == 0: // XP
							$xp = mt_rand(8, 20);
							$color = TextFormat::RED;
							break;
						case $mined % 10 == 0: // XP
							$xp = mt_rand(5, 10);
							$color = TextFormat::GRAY;
							break;
						default:
							$send = false;
							break;
					}
					if($wk = VS::isWeekend()) $xp = $xp * 2;
					$player->getXpManager()->addXp($xp);
					if($send){
						if($essence > 0){
							$e = EnchantmentIdMap::getInstance()->fromId(ED::TRANSMUTATION);
							if($hand->hasEnchantment($e)){
								$essence += $hand->getEnchantment($e)->getLevel() + 1;
							}
							$session = $player->getGameSession()->getEssence();
							$session->addEssence($essence);
							$player->sendTip($color . "+" . number_format($mined) . " blocks mined (" . $essence . " Essence)");
							return;
						}

						$e = EnchantmentIdMap::getInstance()->fromId(ED::XP_MAGNET);
						if($hand->hasEnchantment($e)){
							$player->getXpManager()->addXp($add = $xp * (min($max, min(4, $hand->getEnchantment($e)->getLevel()) + 1)));
							$xp += $add;
						}
						$player->sendTip($color . "+" . number_format($mined) . " blocks mined (" . $xp . "XP" . ($wk ? " [X2]" : "") . ")");

					}
				}
			}
		}
		if($event instanceof EntityShootBowEvent){
			$bow = $event->getBow();
			if($bow->hasEnchantments()){
				foreach($bow->getEnchantments() as $enchantment){
					$ench = $this->getEWE($enchantment);
					if(
						$ench !== null &&
						$ench->isHandled() &&
						!$ench->isDisabled() &&
						$ench->getType()->get(ED::SLOT_SWORD)
					){
						$this->calls->event[$ench->getId()]($event, $enchantment->getLevel());
					}
				}
			}
		}
	}

	public function getItemData(Item $item) : ItemData{
		return new ItemData($item);
	}

	public function calculateCache($e) : void{
		if($e instanceof Player){
			$player = $e;
		}else{
			$player = $e->getEntity();
		}

		if(!$player instanceof Player) return;

		$this->plugin->getScheduler()->scheduleDelayedTask(new class($this, $player) extends \pocketmine\scheduler\Task{

			public function __construct(
				public Enchantments $enchants,
				public Player $player
			){}

			public function onRun() : void{
				/** @var PrisonPlayer $player */
				$player = $this->player;
				$enchants = $this->enchants;
				if(!$player instanceof Player || $player->isClosed()) return;

				$before = $enchants->acache[$player->getName()] ?? [];

				$new = [];
				foreach($player->getArmorInventory()->getContents() as $i => $armor){
					if(
						$armor->hasEnchantments() && (
							$armor->getNamedTag()->getInt(BattleKit::BATTLE_TAG, 0) == 0 ||
							$player->isBattleParticipant()
						)
					){
						foreach($armor->getEnchantments() as $en){
							$eo = $enchants->getEWE($en);
							if($eo->isStackable()){
								/** @var StackableEnchantment $eo */
								if(isset($new[$eo->getId()])){
									$new[$eo->getId()] += $en->getLevel();
								}else{
									$new[$eo->getId()] = $en->getLevel();
								}
								$new[$eo->getId()] = min($new[$eo->getId()], $eo->getMaxStackLevel());
							}else{
								if(isset($new[$eo->getId()])){
									if($new[$eo->getId()] < $en->getLevel()){
										$new[$eo->getId()] = $en->getLevel();
									}
								}else{
									$new[$eo->getId()] = $en->getLevel();
								}
							}
						}
					}
				}
				$enchants->acache[$player->getName()] = $new;

				foreach($new as $id => $level){
					if(!isset($before[$id])) $before[$id] = 0;
				}
				foreach($before as $id => $level){
					$lvl = $new[$id] ?? 0;
					if($level < $lvl){
						if(isset($enchants->calls->equip[$id])){
							$enchants->calls->equip[$id]($player, $level, $lvl);
						}
					}elseif($level > $lvl){
						if(isset($enchants->calls->unequip[$id])){
							$enchants->calls->unequip[$id]($player, $level, $lvl);
						}
					}
				}
			}
		}, 1);
	}

	public function hasCooldown(Player $player) : bool{
		$cooldown = $this->r_cooldown[$player->getXuid()] ?? 0;
		return time() <= $cooldown;
	}

	public function getCooldown(Player $player) : int{
		return $this->r_cooldown[$player->getXuid()] ?? 0;
	}

	public function getCooldownFormatted(Player $player) : string{
		$seconds = $this->getCooldown($player) - time();
		$dtF = new \DateTime("@0");
		$dtT = new \DateTime("@$seconds");
 		return $dtF->diff($dtT)->format("%a days, %h hours, %i minutes");
	}

	public function setCooldown(Player $player, int $cooldown) : void{
		$this->r_cooldown[$player->getXuid()] = time() + $cooldown;
	}

	public function getRoman(int $number) : string{
		$result = "";
		$roman_numerals = [
			"M" => 1000,
			"CM" => 900,
			"D" => 500,
			"CD" => 400,
			"C" => 100,
			"XC" => 90,
			"L" => 50,
			"XL" => 40,
			"X" => 10,
			"IX" => 9,
			"V" => 5,
			"IV" => 4,
			"I" => 1
		];
		foreach($roman_numerals as $roman => $num){
			$matches = intval($number / $num);
			$result .= str_repeat($roman, $matches);
			$number = $number % $num;
		}
		return $result;
	}

}
