<?php namespace prison\enchantments\book;

use pocketmine\item\Item;

use prison\Prison;
use prison\enchantments\type\Enchantment;
use prison\enchantments\EnchantmentData as ED;

use core\utils\TextFormat;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\IntArrayTag;
use pocketmine\nbt\tag\ListTag;

class RedeemedBook extends Item{

	public const TAG_CHANCE = 'applychance';
	public const TAG_COST = 'applycost';
	public const TAG_ENCHANTMENT = 'enchantment';
	public const TAG_SKIP_TIERS = 'skipTiers';
	public const TAG_RANDOM_ID = "item_random_id";
	public const TAG_HAS_REROLLED = "rerolled";
	public const TAG_REROLLED_ENCHANTMENTS = "rerolled_enchantments";

	public function getMaxStackSize(): int{ return 1; }

	public function getApplyCost() : int{ return $this->getNamedTag()->getInt(self::TAG_COST, 50); }

	public function getApplyChance() : int{ return $this->getNamedTag()->getInt(self::TAG_CHANCE, 100); }

	public function hasRerolled() : bool{ return $this->getNamedTag()->getByte(self::TAG_HAS_REROLLED, false); }

	public function canSkipTiers() : bool { return $this->getNamedTag()->getByte(self::TAG_SKIP_TIERS, false); }

	/** @param Enchantment[] $rerolledEnchantments */
	public function setup(Enchantment $enchantment, int $cost = -1, int $chance = -1, bool $skipTiers = false, bool $hasRerolled = false, array $rerolledEnchantments = []) : self{
		$cost = ($cost == -1 ? $enchantment->getRarity() * mt_rand(4, 5) + mt_rand(0, 6) : $cost);
		$chance = ($chance == -1 ? $this->genChance($enchantment->getRarity()) : $chance);

		$level = $enchantment->getStoredLevel() === -1 ? 1 : $enchantment->getStoredLevel();

		$savedEnchantments = new ListTag([], NBT::TAG_End);

		foreach($rerolledEnchantments as $ench){
			$savedEnchantments->push(new IntArrayTag([$ench->getId(), $ench->getStoredLevel()]));
		}

		$this->setNamedTag($this->getNamedTag()
			->setIntArray(self::TAG_ENCHANTMENT, [$enchantment->getId(), $level])
			->setInt(self::TAG_COST, $cost)
			->setInt(self::TAG_CHANCE, $chance)
			->setByte(self::TAG_SKIP_TIERS, $skipTiers)
			->setByte(self::TAG_HAS_REROLLED, $hasRerolled)
			->setTag(self::TAG_REROLLED_ENCHANTMENTS, $savedEnchantments)
		);
		$this->getNamedTag()->setLong(self::TAG_RANDOM_ID, (int) (time() * mt_rand(-999, 999)));
		$this->setCustomName(TextFormat::RESET . $enchantment->getLore($level));

		$lores = [];
		$lores[] = TextFormat::AQUA . $enchantment->getTypeName() . "enchantment";
		$lores[] = " ";
		$lores[] = TextFormat::GRAY . "This book has a " . TextFormat::GREEN . $chance . "%" . TextFormat::GRAY . " chance to be";
		$lores[] = TextFormat::GRAY . "applied onto an item.";
		$lores[] = " ";
		$lores[] = TextFormat::YELLOW . "Apply cost: " . $cost . " XP Levels";
		$lores[] = " ";
		$lores[] = TextFormat::GRAY . "Bring this book to the " . TextFormat::DARK_PURPLE . TextFormat::BOLD . "Enchanter";
		$lores[] = TextFormat::GRAY . "at the " . TextFormat::WHITE . "Hangout" . TextFormat::GRAY . " to enchant an item!";

		if($skipTiers && $level !== 1){
			$lores[] = " ";
			$lores[] = TextFormat::AQUA . "This book can skip the tier system.";
		}

		if($this->hasRerolled()){
			$lores[] = " ";
			$lores[] = TextFormat::AQUA . "This book can no longer be rerolled.";
		}

		foreach($lores as $key => $lore) $lores[$key] = TextFormat::RESET . $lore;

		$this->setLore($lores);
		return $this;
	}

	/** @return Enchantment[] */
	public function generateReroll(int $rarity = -1) : array{
		$rarity = ($rarity != -1 ? $rarity : $this->getEnchant()->getRarity());
		$enchantments = [];

		$amount = ($rarity === ED::RARITY_DIVINE ? 4 : 2);

		for($i = 0; $i < $amount; $i++){
			$ench = Prison::getInstance()->getEnchantments()->getRandomEnchantment($rarity);
			$ench->setStoredLevel(min($ench->getMaxLevel(), (mt_rand(1, 6) === 1 ? 1 : mt_rand(2, 5))));
			$enchantments[] = $ench;
		}

		if($rarity !== ED::RARITY_DIVINE){
			for($i = 0; $i < 2; $i++){
				$rarities = [
					ED::RARITY_COMMON,
					ED::RARITY_UNCOMMON,
					ED::RARITY_RARE,
					ED::RARITY_LEGENDARY
				];
				unset($rarities[$rarity]);

				$ench = Prison::getInstance()->getEnchantments()->getRandomEnchantment($rarities[array_rand($rarities)]);
				$ench->setStoredLevel(min($ench->getMaxLevel(), (mt_rand(1, 5) === 1 ? 1 : mt_rand(2, 5))));
				$enchantments[] = $ench;
			}
		}

		return $enchantments;
	}

	/** @return Enchantment[] */
	public function getRerolledEnchantments() : array{
		$enchantments = [];
		$listTag = $this->getNamedTag()->getListTag(self::TAG_REROLLED_ENCHANTMENTS);

		if(!is_null($listTag)){
			foreach($listTag->getValue() as $tag){
				if(!$tag instanceof IntArrayTag) continue;

				$enchantments[] = Prison::getInstance()->getEnchantments()->getEnchantment($tag->getValue()[0])->setStoredLevel($tag->getValue()[1]);
			}
		}

		return $enchantments;
	}

	public function getEnchant() : ?Enchantment{
		$tag = $this->getNamedTag()->getIntArray(self::TAG_ENCHANTMENT);
		return Prison::getInstance()->getEnchantments()->getEnchantment($tag[0])->setStoredLevel($tag[1]);
	}

	public function genChance(int $rarity = -1) : int{
		switch($rarity){
			case ED::RARITY_COMMON:
				return mt_rand(55, 80);
			case ED::RARITY_UNCOMMON:
				return mt_rand(50, 75);
			case ED::RARITY_RARE:
				return mt_rand(45, 70);
			case ED::RARITY_LEGENDARY:
				return mt_rand(40, 65);
			case ED::RARITY_DIVINE:
				return mt_rand(35, 60);
		}

		return mt_rand(1, 100);
	}

	public function increaseChance(int $chance) : self{ return $this->setChance($this->getApplyChance() + $chance); }

	public function decreaseChance(int $chance) : self{ return $this->setChance($this->getApplyChance() - $chance); }

	public function setChance(int $chance): self {
		return $this->setup($this->getEnchant(), $this->getApplyCost(), min(100, $chance), $this->canSkipTiers(), $this->hasRerolled(), $this->getRerolledEnchantments());
	}
}