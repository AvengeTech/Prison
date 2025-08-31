<?php namespace prison\enchantments;

use pocketmine\block\Block;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\Enchantment as PMEnch;
use pocketmine\item\{
	Axe, 
	Bow,
	Durable,
	Item,
	Pickaxe, 
	Shears,
	Shovel,
	Sword,
	Tool,
};
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

use prison\Prison;
use prison\enchantments\type\Enchantment;
use prison\enchantments\EnchantmentData as ED;
use prison\enchantments\effects\EffectClass;

use core\utils\TextFormat;
use pocketmine\item\enchantment\EnchantmentInstance;
use prison\PrisonPlayer;

class ItemData{
	
	const SKILL_LOOT = 0;
	const SKILL_EXP = 1;
	const SKILL_ = 2;
		
	const SKILL_TREES = [
		self::SKILL_LOOT => [
			1 => 5,
			2 => 10,
			3 => 15,
			4 => 20,
			5 => 25
		],
		self::SKILL_EXP => [
			1 => 1.5,
			2 => 2,
			3 => 2.5,
			4 => 3,
			5 => 4
		],
		self::SKILL_ => [
			
		]
	];
	
	public function __construct(public Item $item){}

	public function send(Player $player) : void{
		$player->getInventory()->setItemInHand($this->getItem());
	}

	public function apply(Item $item) : void{
		/** @var Durable $item */
		/** @var Durable $ti */
		$ti = $this->getItem();
		$item->setDamage($ti->getDamage());
		$item->setNamedTag($ti->getNamedTag());
	}

	public function getItem() : Item{
		return $this->item;
	}

	//easy access
	public function getNamedTag() : CompoundTag{
		return $this->getItem()->getNamedTag();
	}

	public function setNamedTag(CompoundTag $nbt) : void{
		$this->getItem()->setNamedTag($nbt);
	}

	public function getName() : string{
		$item = $this->getItem();
		return $item->getCustomName() == null ? $item->getName() : $item->getCustomName();
	}

	public function setCustomName(string $name) : void{
		$this->getItem()->setCustomName(TextFormat::RESET . $name);
	}

	public function canEdit() : bool{
		return $this->getNamedTag()->getByte("editable", 1);
	}

	public function setEditable(bool $bool) : void{
		$nbt = $this->getNamedTag();
		$nbt->setByte("editable", (int) $bool);
		$this->setNamedTag($nbt);
	}

	//lore stuff
	public function getLevel() : int{
		return $this->getNamedTag()->getInt("level", 1);
	}

	public function setLevel(int $level) : void{
		$nbt = $this->getNamedTag();
		$nbt->setInt("level", $level);
		$this->setNamedTag($nbt);
	}

	public function getXpForNextLevel() : int{
		return 1000;
	}

	public function levelUp() : void{
		$this->setXp(0);
		$this->setLevel($this->getLevel() + 1);
		if($this->getLevel() % 10 == 0 && $this->getLevel() <= 80){
			$this->addSkillPoint();
		}
		$this->getItem()->setLore($this->calculateLores());
	}

	public function sendLevelUpTitle(Player $player) : void{
		/** @var SkyBlockPlayer $player */
		$player->playSound("random.levelup");
		$player->sendTitle(TextFormat::EMOJI_ARROW_UP . " " . TextFormat::RED . ($this->getLevel() - 1) . TextFormat::GRAY . " -> " . TextFormat::GREEN . $this->getLevel() . " " . TextFormat::EMOJI_ARROW_UP, TextFormat::AQUA . "Tool leveled up!", 10, 40, 10);
		$player->sendMessage(TextFormat::GI . "Your " . $this->getItem()->getVanillaName() . " has leveled up" . ($this->getLevel() %10 == 0 ? " and gained " . TextFormat::AQUA . "1 skill point" . TextFormat::GRAY . "!": "!"));
		$player->sendMessage(TextFormat::GI . "Type " . TextFormat::YELLOW . "/tree" . TextFormat::GRAY . " to view your tool's skill tree information!");
	}

	public function getXp() : int{
		return $this->getNamedTag()->getInt("exp", 0);
	}

	public function setXp(int $value) : void{
		$nbt = $this->getNamedTag();
		$nbt->setInt("exp", $value);
		$this->setNamedTag($nbt);
	}

	public function addXp(int $total) : bool{
		if($this->getLevel() === 100) return false;
		$this->setXp($this->getXp() + $total);
		if($this->getXp() >= $this->getXpForNextLevel()){
			$this->levelUp();
			return true;
		}
		return false;
	}

	public function getPrestige() : int{
		return $this->getNamedTag()->getInt("prestige", 0);
	}

	public function setPrestige(int $value) : void{
		$nbt = $this->getNamedTag();
		$nbt->setInt("prestige", $value);
		$this->setNamedTag($nbt);
	}

	public function canPrestige() : bool{
		return $this->getLevel() >= 100;
	}

	public function getPrestigeCost() : int{
		return ($this->getPrestige() + 1) * 1000000;
	}

	public function prestige(Player $player, bool $charge = true) : ?Enchantment{
		/** @var SkyBlockPlayer $player */
		$this->setLevel(1);
		$this->setXp(0);
		$this->setSkillPoints(0);
		$this->setSkillTree(new CompoundTag());
		if($charge) $player->takeTechits($this->getPrestigeCost());
		$player->getGameSession()->getCrates()->addKeys("divine", 2);
		$this->setPrestige($this->getPrestige() + 1);
		
		$random = null;
		if(mt_rand(1, 2) == 1){
			$enchs = $this->getEnchantments();
			if(count($enchs) > 0){
				$canLevelUp = [];
				foreach($enchs as $ench){
					if($ench->getStoredLevel() >= $ench->getMaxLevel()){
						if(
							$ench->canOverclock() &&
							$ench->getStoredLevel() < $ench->getMaxLevel() + 1
						){
							$canLevelUp[] = $ench;
						}
					}else{
						$canLevelUp[] = $ench;
					}
				}
				if(count($canLevelUp) > 0){
					$random = $canLevelUp[array_rand($canLevelUp)];
					$this->getItem()->addEnchantment($random->getEnchantmentInstance($random->getStoredLevel() + 1));
					$random->setStoredLevel($random->getStoredLevel() + 1);
				}
			}
		}

		$this->getItem()->setLore($this->calculateLores());
		return $random;
	}

	public function getSkillPoints() : int{
		return $this->getNamedTag()->getInt("sp", 0);
	}

	public function setSkillPoints(int $value) : void{
		$nbt = $this->getNamedTag();
		$nbt->setInt("sp", $value);
		$this->setNamedTag($nbt);
	}

	public function addSkillPoint(int $total = 1) : void{
		$this->setSkillPoints($this->getSkillPoints() + $total);
	}

	public function getSkillTree() : CompoundTag{
		return $this->getNamedTag()->getTag("tree") ?? new CompoundTag();
	}

	public function setSkillTree(CompoundTag $tag) : void{
		$nbt = $this->getNamedTag();
		$nbt->setTag("tree", $tag);
		$this->setNamedTag($nbt);
	}

	public function getTreeLevel(int $type = self::SKILL_LOOT) : int{
		return $this->getSkillTree()->getInt($type, 0);
	}

	public function setTreeLevel(int $type, int $value) : void{
		$tree = $this->getSkillTree();
		$tree->setInt($type, $value);
		$this->setSkillTree($tree);
	}

	public function applySkillPoint(int $type) : bool{
		$level = $this->getTreeLevel($type);
		if($level >= 5) return false;
		if($this->getSkillPoints() == 0) return false;
		$this->setSkillPoints($this->getSkillPoints() - 1);
		$this->setTreeLevel($type, $level + 1);
		$this->getItem()->setLore($this->calculateLores());
		return true;
	}

	public function isSigned() : bool{
		return $this->getSignature() !== "";
	}

	public function getSignature() : string{
		return $this->getNamedTag()->getString("signature", "");
	}

	public function sign(Player|string $player) : void{
		$nbt = $this->getNamedTag();
		$nbt->setString("signature", $player instanceof Player ? $player->getName() : $player);
		$this->setNamedTag($nbt);

		$this->getItem()->setLore($this->calculateLores());
	}

	public function unsign(): void {
		if (!$this->isSigned()) return;
		$nbt = $this->getNamedTag();
		$nbt->removeTag("signature");
		$this->setNamedTag($nbt);

		$this->getItem()->setLore($this->calculateLores());
	}

	public function hasDeathMessage() : bool{
		return $this->getDeathMessage() !== "";
	}

	public function getDeathMessage() : string{
		return $this->getNamedTag()->getString("deathmessage", "");
	}

	public function setDeathMessage(string $message) : void{
		$nbt = $this->getNamedTag();
		$nbt->setString("deathmessage", $message);
		$this->setNamedTag($nbt);

		$this->getItem()->setLore($this->calculateLores());
	}

	public function getBlocksMined() : int{
		return $this->getNamedTag()->getInt("mined", 0);
	}
	
	public function setBlocksMined(int $amount = 1) : void{
		$nbt = $this->getNamedTag();
		$nbt->setInt("mined", $amount);
		$this->setNamedTag($nbt);

		$this->getItem()->setLore($this->calculateLores());
	}

	public function addBlocksMined(int $amount = 1) : void{
		$this->setBlocksMined($this->getBlocksMined() + $amount);
	}

	public function damage(Block $block) : void{
		$this->getItem()->onDestroyBlock($block, []);
	}

	public function getKills() : int{
		return $this->getNamedTag()->getInt("kills", 0);
	}

	public function setKills(int $kills): void {
		$nbt = $this->getNamedTag();
		$nbt->setInt("kills", $kills);
		$this->setNamedTag($nbt);

		$this->getItem()->setLore($this->calculateLores());
	}

	public function addKill() : void{
		$nbt = $this->getNamedTag();
		$nbt->setInt("kills", $this->getKills() + 1);
		$this->setNamedTag($nbt);

		$this->getItem()->setLore($this->calculateLores());
	}

	public function getCatches() : int{
		return $this->getNamedTag()->getInt("catches", 0);
	}

	public function addCatch() : bool{
		$nbt = $this->getNamedTag();
		$nbt->setInt("catches", $this->getCatches() + 1);
		$this->setNamedTag($nbt);

		$leveledUp = $this->addXp(mt_rand(1, 3));

		$this->getItem()->setLore($this->calculateLores());
		return $leveledUp;
	}

	public function addEnchantment(Enchantment $ench, int $level){
		$item = $this->getItem();
		$instance = $ench->getEnchantmentInstance($level);

		$item->addEnchantment($instance);
		$item->setLore($this->calculateLores());
	}

	public function removeEnchantment(int $id, int $level){
		$item = $this->getItem();

		$ench = EnchantmentIdMap::getInstance()->fromId($id);
		$item->removeEnchantment($ench, $level);
		$item->setLore($this->calculateLores());
	}

	public function getEnchantments() : array{
		$enchs = [];
		$enchantments = $this->getItem()->getEnchantments();
		foreach($enchantments as $ench){
			$id = EnchantmentIdMap::getInstance()->toId($ench->getType());
			$encha = Prison::getInstance()->getEnchantments()->getEnchantment($id);
			if($encha !== null){
				$encha->setStoredLevel($ench->getLevel());
				$enchs[] = $encha;
			}
		}
		return $enchs;
	}

	public function hasEffect() : bool{
		return $this->getEffectId() !== 0;
	}

	public function getEffectId() : int{
		return $this->getNamedTag()->getInt("effectid", 0);
	}

	public function setEffectId(int $id) : void{
		$nbt = $this->getNamedTag();
		$nbt->setInt("effectid", $id);
		$this->setNamedTag($nbt);

		$this->getItem()->setLore($this->calculateLores());
	}

	public function getEffect() : ?EffectClass{
		return Prison::getInstance()->getEnchantments()->getEffects()->getEffectById($this->getEffectId());
	}

	public function calculateLores() : array{
		$item = $this->getItem();

		if(!$item instanceof Durable){
			return [];
		}

		$nl = [];

		if($this->hasDeathMessage()){
			$nl[] = TextFormat::RED . "Death message:";
			$nl[] = "  " . $this->getDeathMessage();
		}

		$enchantments = $item->getEnchantments();
		$elores = [
			ED::RARITY_DIVINE => [],
			ED::RARITY_LEGENDARY => [],
			ED::RARITY_RARE => [],
			ED::RARITY_UNCOMMON => [],
			ED::RARITY_COMMON => [],
		];
		foreach($enchantments as $ench){
			$id = EnchantmentIdMap::getInstance()->toId($ench->getType());
			if($id >= 100){
				$encha = Prison::getInstance()->getEnchantments()->getEnchantment($id);
				if($encha !== null)
					$elores[$encha->getRarity()][] = $encha->getLore($ench->getLevel());
			}
		}
		$enchl = [];
		foreach($elores as $rarity => $lore){
			foreach($lore as $l){
				$enchl[] = $l;
			}
		}
		if(count($enchl) > 0 && $this->hasDeathMessage()){
			$nl[] = " ";
		}
		foreach($enchl as $l) $nl[] = $l;

		if($this->hasEffect()){
			$effect = $this->getEffect();
			$nl[] = " ";
			if($item instanceof Sword){
				$nl[] = TextFormat::RED . "Death animation:";
			}elseif($item instanceof Pickaxe){
				$nl[] = TextFormat::RED . "Mining animation:";
			}elseif($item instanceof Bow){
				$nl[] = TextFormat::RED . "Bow animation:";
			}else{
				$nl[] = TextFormat::RED . "Animation:";
			}
			$nl[] = " " . $effect->getRarityColor() . $effect->getName();
		}

		if (/*($isRod = $item instanceof FishingRod) ||*/$item instanceof Tool) {
			if($item instanceof Axe || $item instanceof Pickaxe || $item instanceof Shovel || $item instanceof Shears){
				$nl[] = " ";
				/* This isn't supposed to be in prisons Miguel lol
				if($item instanceof Pickaxe){
					if($this->getPrestige() > 0) $nl[] = TextFormat::GRAY . "Prestige: " . TextFormat::GREEN . $this->getPrestige();
					$nl[] = TextFormat::GRAY . "Level: " . TextFormat::AQUA . $this->getLevel();
					$nl[] = TextFormat::GRAY . "XP: " . TextFormat::YELLOW . number_format($this->getXp()) . TextFormat::GRAY . "/" . TextFormat::GREEN . number_format($this->getXpForNextLevel());
					$nl[] = TextFormat::GRAY . "Skill points: " . TextFormat::RED . $this->getSkillPoints() . TextFormat::GRAY . " (" . TextFormat::YELLOW . "/tree" . TextFormat::GRAY . ")";
					$nl[] = " ";
				}*/
				$nl[] = TextFormat::GRAY . "Blocks broken: " . number_format($this->getBlocksMined());
			}/* elseif($isRod || $item instanceof Sword){
				if($isRod){
					$nl[] = " ";
					$nl[] = TextFormat::GRAY . "Catches: " . number_format($this->getCatches());
				}
				$nl[] = " ";
				if($this->getPrestige() > 0) $nl[] = TextFormat::GRAY . "Prestige: " . TextFormat::GREEN . $this->getPrestige();
				$nl[] = TextFormat::GRAY . "Level: " . TextFormat::AQUA . $this->getLevel();
				$nl[] = TextFormat::GRAY . "XP: " . TextFormat::YELLOW . number_format($this->getXp()) . TextFormat::GRAY . "/" . TextFormat::GREEN . number_format($this->getXpForNextLevel());
				$nl[] = TextFormat::GRAY . "Skill points: " . TextFormat::RED . $this->getSkillPoints() . TextFormat::GRAY . " (" . TextFormat::YELLOW . "/tree" . TextFormat::GRAY . ")";
				$nl[] = " ";
			}*/
			if($this->getKills() > 0) $nl[] = TextFormat::GRAY . "Player kills: " . number_format($this->getKills());
		}

		if($this->isSigned()){
			$nl[] = " ";
			$nl[] = TextFormat::GRAY . "Signed by: " . TextFormat::YELLOW . $this->getSignature();
		}

		foreach($nl as $key => $l) $nl[$key] = TextFormat::RESET . $l;
		if(count($nl) >= 1){
			if($nl[count($nl) - 1] == TextFormat::RESET . " ") array_pop($nl);
		}
		return $nl;
	}

}