<?php

namespace prison\item;

use core\utils\TextFormat;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use prison\enchantments\EnchantmentData;

abstract class Essence extends Item{
	
	protected const TAG_INIT = 'init';
	protected const TAG_COST = 'cost';
	protected const TAG_RARITY = 'rarity';
	protected const TAG_IS_RAW = 'is_raw';
	
	protected int $cost = 0;
	protected int $rarity = 1;
	protected bool $isRaw = true;

	abstract public function init() : self;

	abstract public function getType() : string;
	
	public function getMaxStackSize() : int{ return 64; }

	public function getCost() : int{ return $this->cost; }

	public function isInitiated() : bool{ return (bool) $this->getNamedTag()->getByte(self::TAG_INIT, false); }

	public function getRarity() : int{ return $this->rarity; }

	public function isRaw() : bool{ return $this->isRaw; }

	public function getRarityName(int $rarity = -1) : string{
		if($rarity === -1) $rarity = $this->getRarity();

		switch($rarity){
			case EnchantmentData::RARITY_COMMON:
				return TextFormat::GREEN . "Common";
			case EnchantmentData::RARITY_UNCOMMON:
				return TextFormat::DARK_GREEN . "Uncommon";
			case EnchantmentData::RARITY_RARE:
				return TextFormat::YELLOW . "Rare";
			case EnchantmentData::RARITY_LEGENDARY:
				return TextFormat::GOLD . "Legendary";
			case EnchantmentData::RARITY_DIVINE:
				return TextFormat::RED . "Divine";
		}

		return ' ';
	}

	protected function deserializeCompoundTag(CompoundTag $tag) : void{
		parent::deserializeCompoundTag($tag);

		$this->cost = $tag->getInt(self::TAG_COST, 0);
		$this->rarity = $tag->getInt(self::TAG_RARITY, 1);
		$this->isRaw = $tag->getByte(self::TAG_IS_RAW, true);
	}

	protected function serializeCompoundTag(CompoundTag $tag) : void{
		parent::serializeCompoundTag($tag);

		if($tag->getByte(self::TAG_INIT, 0) == 1)
			$tag->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));

		$tag->setInt(self::TAG_COST, $this->cost);
		$tag->setInt(self::TAG_RARITY, $this->rarity);
		$tag->setByte(self::TAG_IS_RAW, $this->isRaw);
	}
	
}