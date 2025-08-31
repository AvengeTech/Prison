<?php namespace prison\gangs\battle\arena;

use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\world\World;

use prison\gangs\objects\Gang;

class Arena{

	public string $level;

	public Center $center;
	public array $halves = [];

	public function __construct(
		public int $id,

		public Vector3 $corner1,
		public Vector3 $corner2,

		World $world
	){
		$this->level = $world->getDisplayName();
	}

	public function getId() : int{
		return $this->id;
	}

	public function getCorner1() : Vector3{
		return $this->corner1;
	}

	public function getCorner2() : Vector3{
		return $this->corner2;
	}

	public function getLevel() : ?World{
		return Server::getInstance()->getWorldManager()->getWorldByName($this->level);
	}

	public function getCenter() : Center{
		return $this->center;
	}

	public function setCenter(Center $center) : void{
		$center->setArena($this);
		$this->center = $center;
	}

	public function getHalves() : array{
		return $this->halves;
	}

	public function setHalves(array $halves) : void{
		foreach($halves as $half){
			$half->setArena($this);
		}
		$this->halves = $halves;
	}

	public function getHalf(int $key) : ?Half{
		return $this->halves[$key] ?? null;
	}

	public function getHalfByGang(Gang $gang) : ?Half{
		foreach($this->getHalves() as $half){
			if($half->getGang() == $gang) return $half;
		}
		return null;
	}

}