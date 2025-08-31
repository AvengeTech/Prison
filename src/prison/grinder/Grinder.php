<?php namespace prison\grinder;

use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\Position;

use pocketmine\world\World;
use prison\Prison;
use prison\grinder\mobs\{
	Chicken,
	Cow,
	Mooshroom,
	Pig,
	Sheep
};

class Grinder{

	public $plugin;

	public $spawners = [];

	public function __construct(Prison $plugin){
		$this->plugin = $plugin;

		$this->setup();

		EntityFactory::getInstance()->register(Chicken::class, function(World $world, CompoundTag $nbt) : Chicken{
			return new Chicken(EntityDataHelper::parseLocation($nbt, $world));
		}, ["minecraft:chicken"]);
		EntityFactory::getInstance()->register(Mooshroom::class, function(World $world, CompoundTag $nbt) : Mooshroom{
			return new Mooshroom(EntityDataHelper::parseLocation($nbt, $world));
		}, ["minecraft:mooshroom"]);
		EntityFactory::getInstance()->register(Cow::class, function(World $world, CompoundTag $nbt) : Cow{
			return new Cow(EntityDataHelper::parseLocation($nbt, $world));
		}, ["minecraft:cow"]);
		EntityFactory::getInstance()->register(Pig::class, function(World $world, CompoundTag $nbt) : Pig{
			return new Pig(EntityDataHelper::parseLocation($nbt, $world));
		}, ["minecraft:pig"]);
		EntityFactory::getInstance()->register(Sheep::class, function(World $world, CompoundTag $nbt) : Sheep{
			return new Sheep(EntityDataHelper::parseLocation($nbt, $world));
		}, ["minecraft:sheep"]);
	}

	public function setup() : void{
		foreach(Structure::SPAWNERS as $id => $spawner){
			$pos = $spawner["position"];
			$pos[] = $this->plugin->getServer()->getWorldManager()->getWorldByName($spawner["level"]);

			$this->spawners[] = new Spawner($id, $spawner["mob"], $spawner["spawnRate"], $spawner["distance"], new Position(...$pos));
		}
	}

	public function tick() : void{
		foreach($this->getSpawners() as $spawner){
			$spawner->tick();
		}
	}

	public function getSpawners() : array{
		return $this->spawners;
	}

}