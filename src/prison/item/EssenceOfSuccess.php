<?php namespace prison\item;

use pocketmine\item\{
	Item,
};
use pocketmine\nbt\{
	NBT,
	tag\ListTag
};

use core\utils\TextFormat;
use pocketmine\nbt\tag\CompoundTag;
use prison\enchantments\EnchantmentData;

class EssenceOfSuccess extends Essence{

	protected const TAG_PERCENT = 'percent';

	private int $percent = 0;

	public function getPercent() : int{ return $this->percent; }

	public function getType() : string{ return "s"; }

	public function setup(int $rarity, int $cost = -1, int $percent = -1, bool $isRaw = true) : self{
		$this->rarity = $rarity;
		$this->isRaw = $isRaw;

		if(!$isRaw){
			$this->cost = ($cost === -1 ? $rarity * mt_rand(5, 7) + mt_rand(0, 4) : $cost);
			$this->percent = ($percent === -1 ? $this->getRarityPercentages() : $percent);
		}

		return $this;
	}

	public function init() : self{
		$this->getNamedTag()->setByte(self::TAG_INIT, true);
		
		$this->setCustomName(TextFormat::RESET . TextFormat::GREEN . ($this->isRaw ? "Raw " : "") . "Essence of Success");
		$lores = [];

		if($this->isRaw){
			$lores[] = TextFormat::GRAY . "This item must be refined";
			$lores[] = TextFormat::GRAY . "before use.";
			$lores[] = " ";
			$lores[] = TextFormat::GRAY . "Percentage Range: " . TextFormat::RED . $this->getRarityPercentages(-1, false) . '%' . TextFormat::GRAY . ' - ' . TextFormat::GREEN . $this->getRarityPercentages(-1, false, false) . '%';
			$lores[] = " ";
			$lores[] = TextFormat::GRAY . "Bring this to the " . TextFormat::YELLOW . TextFormat::BOLD . "Refinery" . TextFormat::RESET . TextFormat::GRAY . ",";
			$lores[] = TextFormat::GRAY . "located at " . TextFormat::WHITE . "Spawn";
		}else{
			$chanceColor = ($this->percent <= 15 ? TextFormat::RED : ($this->percent <= 30 ? TextFormat::GOLD : ($this->percent < 45 ? TextFormat::YELLOW : TextFormat::GREEN)));

			$lores[] = TextFormat::GRAY . "Use this item to increase";
			$lores[] = TextFormat::GRAY . "the percentages of other";
			$lores[] = TextFormat::GRAY . "items.";
			$lores[] = " ";
			$lores[] = TextFormat::GRAY . "Percent Increase: " . $chanceColor . $this->percent . "%";
			$lores[] = " ";
			$lores[] = TextFormat::DARK_AQUA . "Cost: " . $this->cost . " Essence";
			$lores[] = " ";
			$lores[] = TextFormat::GRAY . "Bring this to the " . TextFormat::BLUE . TextFormat::BOLD . "Conjuror" . TextFormat::RESET . TextFormat::GRAY . ",";
			$lores[] = TextFormat::GRAY . "located at " . TextFormat::WHITE . "Spawn";
		}
		foreach($lores as $key => $lore) $lores[$key] = TextFormat::RESET . $lore;

		$this->setLore($lores);
		$this->getNamedTag()->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));

		return $this;
	}

	public function getRarityPercentages(int $rarity = -1, bool $random = true, bool $min = true) : int{
		if($rarity === -1) $rarity = $this->getRarity();

		switch($rarity){
			case EnchantmentData::RARITY_COMMON:
				return ($random ? mt_rand(1, 14) : ($min ? 1 : 14));
			case EnchantmentData::RARITY_UNCOMMON:
				return ($random ? mt_rand(15, 24) : ($min ? 15 : 24));
			case EnchantmentData::RARITY_RARE:
				return ($random ? mt_rand(25, 34) : ($min ? 25 : 34));
			case EnchantmentData::RARITY_LEGENDARY:
				return ($random ? mt_rand(35, 44) : ($min ? 35 : 44));
			case EnchantmentData::RARITY_DIVINE:
				return ($random ? mt_rand(45, 55) : ($min ? 45 : 55));
		}

		return mt_rand(1, 100);
	}

	protected function deserializeCompoundTag(CompoundTag $tag) : void{
		parent::deserializeCompoundTag($tag);

		$this->percent = $tag->getInt(self::TAG_PERCENT, 0);

		$randomId = $tag->getLong("item_random_id", -1);

		if($randomId !== -1){
			$tag->removeTag("item_random_id");
		}
	}

	protected function serializeCompoundTag(CompoundTag $tag) : void{
		parent::serializeCompoundTag($tag);

		$tag->setInt(self::TAG_PERCENT, $this->percent);
	}
}