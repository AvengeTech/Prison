<?php namespace prison\item;

use pocketmine\item\Item;
use pocketmine\nbt\{
	NBT,
	tag\ListTag
};

use core\utils\TextFormat;
use pocketmine\nbt\tag\CompoundTag;

class Nametag extends Item{

	protected const TAG_INIT = "init";

	public function getMaxStackSize() : int{
		return 64;
	}

	public function isInitiated() : bool{
		return (bool) $this->getNamedTag()->getByte(self::TAG_INIT, 0);
	}

	public function init() : void{
		$this->getNamedTag()->setByte(self::TAG_INIT, true);

		$this->setCustomName(TextFormat::RESET . TextFormat::GOLD . "Nametag");
		$lores = [];
		$lores[] = TextFormat::GRAY . "Bring this to the " . TextFormat::DARK_GRAY . TextFormat::BOLD . "Blacksmith" . TextFormat::RESET . TextFormat::GRAY . ",";
		$lores[] = TextFormat::GRAY . "located at the " . TextFormat::WHITE . "Hangout" . TextFormat::GRAY . " to rename any";
		$lores[] = TextFormat::GRAY . "tool/armor piece!";
		$lores[] = " ";
		$lores[] = TextFormat::GRAY . "This can only be used one time.";
		foreach($lores as $key => $lore) $lores[$key] = TextFormat::RESET . $lore;

		$this->setLore($lores);

		$this->getNamedTag()->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));
	}

	protected function serializeCompoundTag(CompoundTag $tag) : void{
		parent::serializeCompoundTag($tag);

		if($tag->getByte(self::TAG_INIT, 0) === 1)
			$tag->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));
	}
}