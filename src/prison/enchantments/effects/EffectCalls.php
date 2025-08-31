<?php namespace prison\enchantments\effects;

use pocketmine\color\Color;
use pocketmine\player\Player;
use pocketmine\world\{
	Position,
	sound\FizzSound,
	sound\BlazeShootSound,

	particle\EntityFlameParticle,
	particle\MobSpawnParticle,
	particle\InkParticle,
	particle\DustParticle,
	particle\SplashParticle
};
use pocketmine\entity\{
    Living,
    Location,
};

use prison\Prison;
use prison\enchantments\effects\tasks\{
	FloodAnimationTask,
	ThorsWrathAnimationTask,
	AuraAnimationTask,
	BurnAnimationTask,
	SnowballAnimationTask,
	LavaRainAnimationTask
};
use prison\enchantments\effects\entities\{
	WitchcraftBat,
	ExplodingChicken,
	CreepySpider,
	CreepySilverfish,
	CreepySnowGolem,
	FloppyFish,
	FlyingLlama,
	BoomTNT,
	WoahVillager,
	DreamBoat,
	El,
	GG,
	Tombstone
};

use core\utils\{
	PlaySound
};

class EffectCalls extends EffectIds{

	public $calls = [];

	//Second parameter is either a player or a position
	public function __construct(){
		$this->calls = [
			//Common
			self::SHOOK => function(Player $killer, $dead){

			},
			self::POOF => function(Player $killer, $dead){
				$killer->getWorld()->addSound($dead, new FizzSound());
				for($i = 0; $i < mt_rand(5, 7); $i++){
					$killer->getWorld()->addParticle($dead->add(mt_rand(-10, 10) / 10, mt_rand(0, 20) / 10, mt_rand(-10, 10) / 10), new MobSpawnParticle());
				}
			},
			self::COOKED => function(Player $killer, $dead){
				$killer->getWorld()->addSound($dead, new FizzSound());
				$killer->getWorld()->addSound($dead, new BlazeShootSound());
				for($i = 0; $i < mt_rand(4, 6); $i++){
					$killer->getWorld()->addParticle($dead->add(mt_rand(-10, 10) / 10, 0, mt_rand(-10, 10) / 10), new EntityFlameParticle());
				}
			},
			self::FLOOD => function(Player $killer, $dead){
				$task = new FloodAnimationTask($dead instanceof Player ? $dead->getPosition() : $dead, 2);
				Prison::getInstance()->getScheduler()->scheduleDelayedTask($task, 1);
			},
			self::FLURRY => function(Player $killer, $dead){
				$task = new SnowballAnimationTask($dead instanceof Player ? $dead->getPosition() : $dead, 3);
				Prison::getInstance()->getScheduler()->scheduleDelayedTask($task, 1);
			},
			self::SPLASH => function(Player $killer, $dead){
				$killer->getWorld()->addSound($dead, new PlaySound($dead, "mob.fish.flop"));
				$killer->getWorld()->addSound($dead, new PlaySound($dead, "mob.fish.hurt"));
				for($i = 0; $i < mt_rand(20, 30); $i++){
					$killer->getWorld()->addParticle($dead->add(mt_rand(-10, 10) / 10, 0, mt_rand(-10, 10) / 10), new SplashParticle());
				}
			},
			self::DREAM => function(Player $killer, $dead){
				$entity = new DreamBoat(Location::fromObject($dead->add(0, 0.3, 0), $killer->getWorld()), $dead->add(0, 0.3, 0));
				$entity->spawnToAll();
			},

			//Uncommon
			self::L => function(Player $killer, $dead){
				$entity = new El(Location::fromObject($dead, $killer->getWorld()), $dead);
				$entity->spawnToAll();
			},
			self::LAVA_RAIN => function(Player $killer, $dead){
				$task = new LavaRainAnimationTask($dead instanceof Player ? $dead->getPosition() : $dead, 2);
				Prison::getInstance()->getScheduler()->scheduleDelayedTask($task, 1);
			},
			self::TNT => function(Player $killer, $dead){
				$entity = new BoomTNT(Location::fromObject($dead, $killer->getWorld()));
				$entity->spawnToAll();
			},
			self::LITTLE_MERMAID => function(Player $killer, $dead){
				$entity = new FloppyFish(Location::fromObject($dead, $killer->getWorld()), $dead);
				$entity->spawnToAll();
			},
			self::BLACKOUT => function(Player $killer, $dead){
				$killer->getWorld()->addSound($dead, new PlaySound($dead, "mob.slime.squish"));
				for($i = 0; $i < mt_rand(12, 16); $i++){
					$killer->getWorld()->addParticle($dead->add(mt_rand(-10, 10) / 10, mt_rand(0, 20) / 10, mt_rand(-10, 10) / 10), new InkParticle());
					$killer->getWorld()->addParticle($dead->add(mt_rand(-10, 10) / 10, mt_rand(0, 20) / 10, mt_rand(-10, 10) / 10), new DustParticle( new Color(0, 0, 0)));
				}
			},
			self::THORS_WRATH => function(Player $killer, $dead){
				$killer->getWorld()->addSound($dead, new PlaySound($dead, "ambient.weather.thunder"));
				$task = new ThorsWrathAnimationTask($dead instanceof Player ? $dead->getPosition() : $dead, 3);
				Prison::getInstance()->getScheduler()->scheduleDelayedTask($task, 1);
			},

			//Rare
			self::RIP => function(Player $killer, $dead){
				$pos = $dead instanceof Player ? $dead->getPosition() : $dead;
				$killer->getWorld()->addSound($pos, new PlaySound($pos, "block.bell.hit"));

				$entity = new Tombstone(Location::fromObject($pos, $dead->getWorld()), $pos, ($dead instanceof Living ? $dead->getName() : "rando"));
				$entity->lookAt($killer->getPosition());
				$entity->setRotation($entity->getLocation()->getYaw() - 210, 0);
				$entity->spawnToAll();

			},
			self::AURA => function(Player $killer, $dead){
				$pos = $dead instanceof Player ? $dead->getPosition() : $dead;
				$pos = new Position($pos->x, $pos->y + 1.5, $pos->z, $pos->getWorld());
				$task = new AuraAnimationTask($pos, 2);
				Prison::getInstance()->getScheduler()->scheduleDelayedTask($task, 1);
			},
			self::COLD_AND_CREEPY => function(Player $killer, $dead){
				for($i = 0; $i <= mt_rand(2, 4); $i++){
					$entity = new CreepySnowGolem(Location::fromObject($dead, $killer->getWorld()), $dead);
					$entity->spawnToAll();
				}
			},
			self::BURN => function(Player $killer, $dead){
				$pos = $dead instanceof Player ? $dead->getPosition() : $dead;
				$pos = new Position($pos->x, $pos->y + 1.5, $pos->z, $pos->getWorld());
				$task = new BurnAnimationTask($pos, 2);
				Prison::getInstance()->getScheduler()->scheduleDelayedTask($task, 1);
			},
			self::TRADE_OFF => function(Player $killer, $dead){
				$entity = new WoahVillager(Location::fromObject($dead, $killer->getWorld()), $dead);
				$entity->spawnToAll();
			},

			//Legendary
			self::WITCHCRAFT => function(Player $killer, $dead){
				$killer->getWorld()->addSound($dead, new PlaySound($dead, "mob.bat.takeoff"));
				for($i = 0; $i < 10; $i++){
					$entity = new WitchcraftBat(Location::fromObject($dead, $killer->getWorld()), $dead);
					$entity->spawnToAll();
				}
			},
			self::CHICKEN => function(Player $killer, $dead){
				$entity = new ExplodingChicken(Location::fromObject($dead, $killer->getWorld()), $dead);
				$entity->spawnToAll();
			},
			self::APOCALYPSE => function(Player $killer, $dead){

			},
			self::DEAD_RIDER => function(Player $killer, $dead){

			},
			self::CREEPY_CRAWLY => function(Player $killer, $dead){
				$killer->getWorld()->addSound($dead, new PlaySound($dead, "mob.snowgolem.hurt"));
				for($i = 0; $i <= mt_rand(1, 3); $i++){
					$entity = new CreepySpider(Location::fromObject($dead, $killer->getWorld()), $dead);
					$entity->spawnToAll();
				}
				for($i = 0; $i <= mt_rand(1, 3); $i++){
					$entity = new CreepySilverfish(Location::fromObject($dead, $killer->getWorld()), $dead);
					$entity->spawnToAll();
				}
			},
			self::ENDERS_WRATH => function(Player $killer, $dead){

			},
			self::EXPLOSIVE_SURPRISE => function(Player $killer, $dead){
				$entity = new FlyingLlama(Location::fromObject($dead, $killer->getWorld()), $dead);
				$entity->spawnToAll();
			},
			self::GG => function(Player $killer, $dead){
				$entity = new GG(Location::fromObject($dead, $killer->getWorld()), $dead);
				$entity->spawnToAll();
			},
		];
	}

}