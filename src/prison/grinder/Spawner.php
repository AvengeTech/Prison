<?php namespace prison\grinder;

use pocketmine\world\Position;
use pocketmine\entity\{
	Location
};
use pocketmine\player\Player;

use prison\grinder\mobs\Animal;

class Spawner{

	public $id;
	public $mob;

	public $spawnRate;
	public $distance;
	public $position;

	public $ticks = 0;

	public function __construct(int $id, string $mob, int $spawnRate, int $distance, Position $position){
		$this->id = $id;
		$this->mob = $mob;

		$this->spawnRate = $spawnRate;
		$this->distance = $distance;
		$this->position = $position;
	}

	public function getId() : int{
		return $this->id;
	}

	public function getMob() : string{
		return $this->mob;
	}

	public function getSpawnRate() : int{
		return $this->spawnRate;
	}

	public function getDistance() : int{
		return $this->distance;
	}

	public function getPosition() : Position{
		return $this->position;
	}

	public function tick() : void{
		$this->ticks++;
		if($this->ticks % $this->getSpawnRate() == 0){
			$pos = $this->getPosition();
			$near = $pos->getWorld()->getNearestEntity($pos, $this->getDistance(), Player::class);
			if($near != null){
				$pos = $pos->add(mt_rand(-2, 2), 2, mt_rand(-2, 2));
				$pos = new Position($pos->getX(), $pos->getY(), $pos->getZ(), $this->getPosition()->getWorld());
				$entity = $this->getEntity($pos);
				$entity->spawnToAll();
			}
		}
	}

	public function getEntity(Position $pos) : Animal{
		$class = "\\prison\\grinder\\mobs\\" . $this->getMob();
		return new $class(Location::fromObject($pos, $pos->getWorld()));
	}

}