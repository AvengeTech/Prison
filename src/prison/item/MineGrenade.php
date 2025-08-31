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

use core\utils\TextFormat;

/** 
 * Outdated & unimplemented item
 * @deprecated 1.9.0
 */
class MineGrenade extends ProjectileItem{

	public function __construct(){
		parent::__construct(new ItemIdentifier(ItemIds::GUNPOWDER, 1), "Mine Grenade");
	}

	public function init() : void{
		$this->setCustomName(TextFormat::RESET . TextFormat::YELLOW . "Mine Grenade");

		$lores = [];
		$lores[] = "Throw this in any mine to";
		$lores[] = "blow up it's contents. 3 can";
		$lores[] = "be thrown at a time!";
		foreach($lores as $key => $lore) $lores[$key] = TextFormat::RESET . TextFormat::GRAY . $lore;
		$this->setLore($lores);

		$this->getNamedTag()->setTag("ench", new ListTag([], NBT::TAG_Compound));
	}

	public function getThrowForce(): float{
		return 0.6;
	}

	public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems) : ItemUseResult{
		if($this->getMeta() != 1) return ItemUseResult::FAIL();
		$session = $player->getGameSession()->getMines();
		if(!$session->inMine()){
			$player->sendMessage(TextFormat::RN . "Mine Grenades can only be thrown in mines!");
			return ItemUseResult::FAIL();
		}
		if($session->getMine()->pvp()){
			$player->sendMessage(TextFormat::RN . "Mine Grenades cannot be thrown in PvP mines!");
			return ItemUseResult::FAIL();
		}
		if(
			isset(Prison::getInstance()->getEnchantments()->gncd[$player->getName()]) &&
			Prison::getInstance()->getEnchantments()->gncd[$player->getName()]["total"] == 0 &&
			Prison::getInstance()->getEnchantments()->gncd[$player->getName()]["time"] - time() > 0 &&
			!$player->isSn3ak()
		){
			$player->sendMessage(TextFormat::RN . "You cannot use another Mine Grenade for " . TextFormat::RED . (Prison::getInstance()->getEnchantments()->nukecd[$player->getName()] - time()) . " seconds");
			return ItemUseResult::FAIL();
		}

		if(!isset(Prison::getInstance()->getEnchantments()->gncd[$player->getName()])){
			Prison::getInstance()->getEnchantments()->gncd[$player->getName()] = [
				"total" => 2,
				"time" => time() + 150
			];
		}else{
			Prison::getInstance()->getEnchantments()->gncd[$player->getName()]["total"]--;
		}
		return parent::onClickAir($player, $directionVector);
	}

	protected function createEntity(Location $location, Player $thrower): Throwable{
		return new \prison\entity\MineGrenade($location, $thrower);
	}
}