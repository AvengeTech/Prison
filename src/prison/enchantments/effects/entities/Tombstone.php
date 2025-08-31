<?php namespace prison\enchantments\effects\entities;

use pocketmine\entity\{
	Human,
	Location,
	Skin
};
use pocketmine\world\{
	sound\PopSound,
	particle\SmokeParticle
};
use pocketmine\math\Vector3;
use pocketmine\event\entity\EntityDamageEvent;

use core\etc\pieces\skin\Skin as CSkin;
use core\utils\TextFormat;

class Tombstone extends Human{

	const LIFESPAN = 40; //ticks

	public $deathposition;

	public $aliveTicks = 0;

	public function __construct(Location $location, Vector3 $deathposition, string $name = "rando"){
		$this->deathposition = $dp = $deathposition;

		$customGeometry = json_decode(file_get_contents("/[REDACTED]/skins/custom/tombstone.geo.json"), true, 512, JSON_THROW_ON_ERROR);
		$geometry = "geometry.unknown";
		$geometryData = json_encode($customGeometry, JSON_THROW_ON_ERROR);
		$this->setSkin(new Skin("Tombstone", CSkin::getSkinData("custom/tombstone"), "", $geometry, $geometryData));
		$this->setNametag(TextFormat::RED . "Here lies, " . $name);

		$this->setCanSaveWithChunk(false);
		parent::__construct($location, $this->getSkin());
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		$this->aliveTicks++;

		if($this->aliveTicks > self::LIFESPAN && $this->isAlive()){ //buhbye
			for($i = 0; $i < 5; $i++)
				$this->getWorld()->addParticle($this->getPosition(), new SmokeParticle());

			$this->getWorld()->addSound($this->getPosition(), new PopSound());
			$this->flagForDespawn();
		}

		return $this->isAlive();
	}

	public function attack(EntityDamageEvent $source) : void{
		$source->cancel();
	}

}