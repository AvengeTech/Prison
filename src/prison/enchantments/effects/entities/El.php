<?php namespace prison\enchantments\effects\entities;

use pocketmine\entity\{
	Human,
	Location,
	Skin
};
use pocketmine\color\Color;
use pocketmine\world\{
	particle\DustParticle,
	particle\SmokeParticle
};
use pocketmine\math\Vector3;
use pocketmine\event\entity\EntityDamageEvent;

use core\utils\{
	PlaySound,
};
use core\etc\pieces\skin\Skin as CSkin;

class El extends Human{

	const STATEMENTS = [
		"Git rekt n00b",
		"LLLLLLLLL",
		"Git gud scrub",
	];

	const LIFESPAN = 60; //ticks

	public $deathposition;

	public $aliveTicks = 0;

	public function __construct(Location $loc, Vector3 $deathposition){
		$this->deathposition = $dp = $deathposition;

		$customGeometry = json_decode(file_get_contents("/[REDACTED]/skins/custom/el.geo.json"), true, 512, JSON_THROW_ON_ERROR);
		$geometry = "geometry.el";
		$geometryData = json_encode($customGeometry, JSON_THROW_ON_ERROR);
		$this->setSkin(new Skin("El", CSkin::getSkinData("custom/el"), "", $geometry, $geometryData));

		$this->setCanSaveWithChunk(false);
		parent::__construct($loc, $this->getSkin());

		$this->getWorld()->addSound($this->getPosition(), new PlaySound($this->getPosition(), "firework.launch"));
		$this->setNameTagVisible(true);
		$this->setNameTagAlwaysVisible(true);
		//$this->setNametag(TextFormat::YELLOW . self::STATEMENTS[array_rand(self::STATEMENTS)]);

		$this->motion->y = 1;
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		$this->aliveTicks++;

		if($this->onGround) $this->jump();

		$this->setRotation($this->getLocation()->getYaw() + 15, $this->getLocation()->getPitch());

		if($this->aliveTicks % 10 == 0){
			for($i = 0; $i <= 5; $i++){
				$this->getWorld()->addParticle($this->getPosition()->add(mt_rand(-10, 10) / 10, 0, mt_rand(-10, 10) / 10), new DustParticle(new Color(mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255))));
			}
		}

		if($this->aliveTicks > self::LIFESPAN && $this->isAlive()){ //buhbye
			for($i = 0; $i < 3; $i++)
				$this->getWorld()->addParticle($this->getPosition(), new SmokeParticle());

			$this->flagForDespawn();
		}

		return $this->isAlive();
	}

	public function attack(EntityDamageEvent $source) : void{
		$source->cancel();
	}

	public function jump() : void{
		$this->motion->y = 0.5;
	}

}