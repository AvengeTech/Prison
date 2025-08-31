<?php

namespace prison\grinder\mobs;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\block\utils\DyeColor;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;

use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\DyeColorIdMap;
use pocketmine\nbt\tag\CompoundTag;

class Sheep extends Animal {

	public const WHITE = 0;
	public const ORANGE = 1;
	public const MAGENTA = 2;
	public const LIGHT_BLUE = 3;
	public const YELLOW = 4;
	public const LIME = 5;
	public const PINK = 6;
	public const GRAY = 7;
	public const LIGHT_GRAY = 8;
	public const CYAN = 9;
	public const PURPLE = 10;
	public const BLUE = 11;
	public const BROWN = 12;
	public const GREEN = 13;
	public const RED = 14;
	public const BLACK = 15;

	public array $colors = [];

	public $width = 0.9;
	public $height = 1.3;

	public int $color = self::WHITE;
	public bool $sheared = false;

	protected function initEntity(CompoundTag $nbt): void {
		$this->colors = [
			self::WHITE => DyeColor::WHITE(),
			self::ORANGE => DyeColor::ORANGE(),
			self::MAGENTA => DyeColor::MAGENTA(),
			self::LIGHT_BLUE => DyeColor::LIGHT_BLUE(),
			self::YELLOW => DyeColor::YELLOW(),
			self::LIME => DyeColor::LIME(),
			self::PINK => DyeColor::PINK(),
			self::GRAY => DyeColor::GRAY(),
			self::LIGHT_GRAY => DyeColor::LIGHT_GRAY(),
			self::CYAN => DyeColor::CYAN(),
			self::PURPLE => DyeColor::PURPLE(),
			self::BLUE => DyeColor::BLUE(),
			self::BROWN => DyeColor::BROWN(),
			self::GREEN => DyeColor::GREEN(),
			self::RED => DyeColor::RED(),
			self::BLACK => DyeColor::BLACK(),
		];
		parent::initEntity($nbt);
	}

	public function getName(): string {
		return "Sheep";
	}

	public function getDrops(): array {
		$drops = [];
		array_push($drops, VanillaBlocks::WOOL()->setColor($this->getColor())->asItem());
		return $drops;
	}

	public function getMaxHealth(): int {
		return 8;
	}

	public function getColor(): DyeColor {
		return $this->colors[$this->color];
	}

	public function setColor(int $color): void {
		$this->color = $color;
		$this->networkPropertiesDirty = true;
	}

	public function setSheared(bool $bool): void {
		$this->sheared = $bool;
		$this->networkPropertiesDirty = true;
	}

	public function isSheared(): bool {
		return $this->sheared;
	}

	protected function getInitialSizeInfo(): EntitySizeInfo {
		return new EntitySizeInfo($this->height, $this->width);
	}

	public static function getNetworkTypeId(): string {
		return "minecraft:sheep";
	}

	protected function syncNetworkData(EntityMetadataCollection $properties): void {
		parent::syncNetworkData($properties);
		$properties->setByte(EntityMetadataProperties::COLOR, DyeColorIdMap::getInstance()->toId($this->getColor()));
		$properties->setGenericFlag(EntityMetadataFlags::SHEARED, $this->isSheared());
	}
}
