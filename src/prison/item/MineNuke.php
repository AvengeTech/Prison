<?php namespace prison\item;

use pocketmine\item\{
	Item,
	ItemIdentifier,
	ItemIds,
	ItemUseResult,
	ProjectileItem
};
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Throwable;
use pocketmine\player\Player;
use pocketmine\math\Vector3;
use pocketmine\nbt\{
	tag\ListTag,
	NBT
};

use prison\Prison;
use prison\PrisonPlayer;

use core\utils\TextFormat;
use pocketmine\nbt\tag\CompoundTag;

class MineNuke extends ProjectileItem{

	protected const TAG_INIT = "init";

	public function init() : void{
		$this->setNamedTag($this->getNamedTag()->setByte(self::TAG_INIT, 1));
		$this->setCustomName(TextFormat::RESET . TextFormat::RED . "Mine Nuke");

		$lores = [];
		$lores[] = "Throw this in any mine to";
		$lores[] = TextFormat::RED . "NUKE " . TextFormat::GRAY . "it's contents!";
		foreach($lores as $key => $lore) $lores[$key] = TextFormat::RESET . TextFormat::GRAY . $lore;
		$this->setLore($lores);

		$this->getNamedTag()->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));
	}

	public function getThrowForce(): float{
		return 0.6;
	}

	public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems) : ItemUseResult{
		/** @var PrisonPlayer $player */
		if($this->getNamedTag()->getByte(self::TAG_INIT, 0) != 1) return ItemUseResult::FAIL();
		$session = $player->getGameSession()->getMines();
		if(!$session->inMine()){
			$player->sendMessage(TextFormat::RN . "Mine Nukes can only be thrown in mines!");
			return ItemUseResult::FAIL();
		}
		if($session->getMine()->pvp()){
			$player->sendMessage(TextFormat::RN . "Mine Nukes cannot be thrown in PvP mines!");
			return ItemUseResult::FAIL();
		}
		if(
			isset(Prison::getInstance()->getEnchantments()->nukecd[$player->getName()]) &&
			Prison::getInstance()->getEnchantments()->nukecd[$player->getName()] - time() > 0 &&
			!$player->isSn3ak()
		){
			$player->sendMessage(TextFormat::RN . "You cannot use another Mine Nuke for " . TextFormat::RED . (Prison::getInstance()->getEnchantments()->nukecd[$player->getName()] - time()) . " seconds");
			return ItemUseResult::FAIL();
		}

		Prison::getInstance()->getEnchantments()->nukecd[$player->getName()] = time() + 120;
		return parent::onClickAir($player, $directionVector, $returnedItems);
	}

	protected function createEntity(Location $location, Player $thrower): Throwable{
		return new \prison\entity\MineNuke($location, $thrower);
	}

	protected function serializeCompoundTag(CompoundTag $tag) : void{
		parent::serializeCompoundTag($tag);

		if($tag->getByte(self::TAG_INIT, 0) === 1)
			$tag->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));
	}
}