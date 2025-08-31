<?php namespace prison\item;

use pocketmine\item\{Item, ItemIdentifier, ItemUseResult, ProjectileItem};
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
use pocketmine\block\utils\DyeColor;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\Binary;

class HasteBomb extends ProjectileItem{
	
	protected const TAG_INIT = "init";
	protected const TAG_COLOR = "customColor"; //TAG_Int

	public function __construct(
		private ItemIdentifier $identifier,
		protected string $name = "Unknown",
		private array $enchantmentTags = []
	){
		parent::__construct($identifier, $name, $enchantmentTags);

		$this->setColor(DyeColor::YELLOW());
	}

	public function setColor(DyeColor $color) : self{
		$this->getNamedTag()->setInt(self::TAG_COLOR, Binary::signInt($color->getRgbValue()->toARGB()));
		return $this;
	}

	public function init() : void{
		$this->getNamedTag()->setByte(self::TAG_INIT, 1);
		$this->setCustomName(TextFormat::RESET . TextFormat::YELLOW . "Haste Bomb");

		$lores = [];
		$lores[] = "Throw this at the ground to";
		$lores[] = "give " . TextFormat::YELLOW . "Haste II " . TextFormat::GRAY . "to everyone";
		$lores[] = "within " . TextFormat::AQUA . "3 blocks" . TextFormat::GRAY . " for " . TextFormat::YELLOW . "5 minutes";
		foreach($lores as $key => $lore) $lores[$key] = TextFormat::RESET . TextFormat::GRAY . $lore;
		$this->setLore($lores);

		$this->getNamedTag()->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));
	}

	public function getThrowForce(): float{
		return 0.4;
	}

	public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems) : ItemUseResult{
		/** @var PrisonPlayer $player */
		if($this->getNamedTag()->getByte(self::TAG_INIT, 0) != 1) return ItemUseResult::FAIL();
		if(
			isset(Prison::getInstance()->getEnchantments()->hbcd[$player->getName()]) &&
			Prison::getInstance()->getEnchantments()->hbcd[$player->getName()] - time() > 0 &&
			!$player->isSn3ak() //pog
		){
			$player->sendMessage(TextFormat::RN . "You cannot use another Haste Bomb for " . TextFormat::RED . (Prison::getInstance()->getEnchantments()->hbcd[$player->getName()] - time()). " seconds");
			return ItemUseResult::FAIL();
		}

		Prison::getInstance()->getEnchantments()->hbcd[$player->getName()] = time() + 120;
		return parent::onClickAir($player, $directionVector, $returnedItems);
	}

	protected function createEntity(Location $location, Player $thrower): Throwable{
		return new \prison\entity\HasteBomb($location, $thrower);
	}

	protected function serializeCompoundTag(CompoundTag $tag) : void{
		parent::serializeCompoundTag($tag);

		if($tag->getByte(self::TAG_INIT, 0) === 1)
			$tag->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));
	}
}