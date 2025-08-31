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

class UnboundTome extends Item{

	protected const TAG_CHANCE = 'chance';
	protected const TAG_COST = 'cost';
	protected const TAG_INIT = 'init';

	private int $chance = 0;
	private int $cost = 0;



	public function getMaxStackSize() : int{ return 64; }

	public function getReturnChance() : int{ return $this->chance; }

	public function getCost() : int{ return $this->cost; }

	public function isInitiated() : bool{ return (bool) $this->getNamedTag()->getByte(self::TAG_INIT, false); }

	public function init(int $chance = -1): self {
		$this->chance = ($chance == -1 ? mt_rand(30, 80) : $chance);
		$this->cost = (int) ($chance / 10 * 3);

		$chanceColor = ($chance <= 20 ? TextFormat::RED : ($chance <= 50 ? TextFormat::GOLD : ($chance <= 75 ? TextFormat::YELLOW : TextFormat::GREEN)));
		
		$this->setCustomName(TextFormat::RESET . TextFormat::AQUA . "Unbound Tome");
		$lores = [];
		$lores[] = TextFormat::GRAY . "This item is used to remove";
		$lores[] = TextFormat::GRAY . "an enchantment from an";
		$lores[] = TextFormat::GRAY . "item of your choice";
		$lores[] = " ";
		$lores[] = TextFormat::GRAY . "Return chance: " . $chanceColor . $chance . "%";
		$lores[] = " ";
		$lores[] = TextFormat::YELLOW . "Cost: " . $this->cost . " XP Levels";
		$lores[] = " ";
		$lores[] = TextFormat::GRAY . "Bring this to the " . TextFormat::DARK_GRAY . TextFormat::BOLD . "Blacksmith" . TextFormat::RESET . TextFormat::GRAY . ",";
		$lores[] = TextFormat::GRAY . "located at " . TextFormat::WHITE . "Spawn";
		foreach($lores as $key => $lore) $lores[$key] = TextFormat::RESET . $lore;

		$this->setLore($lores);
		
		$this->getNamedTag()->setByte(self::TAG_INIT, true);
		$this->getNamedTag()->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));
		return $this;
	}

	public function increaseChance(int $chance) : self{ return $this->setChance($this->getReturnChance() + $chance); }

	public function decreaseChance(int $chance) : self{ return $this->setChance($this->getReturnChance() - $chance); }

	public function setChance(int $chance): self {
		return $this->init(min(100, $chance));
	}

	protected function deserializeCompoundTag(CompoundTag $tag) : void{
		parent::deserializeCompoundTag($tag);

		$this->chance = $tag->getInt(self::TAG_CHANCE, 0);
		$this->cost = $tag->getInt(self::TAG_COST, 0);
	}

	protected function serializeCompoundTag(CompoundTag $tag) : void{
		parent::serializeCompoundTag($tag);
		
		if($tag->getByte(self::TAG_INIT, 0) == 1)
			$tag->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));

		$tag->setInt(self::TAG_CHANCE, $this->chance);
		$tag->setInt(self::TAG_COST, $this->cost);
	}
}