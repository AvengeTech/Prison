<?php namespace prison\gangs\battle\arena;

use pocketmine\math\Vector3;
use pocketmine\world\{
	World as Level
};
use pocketmine\entity\Location;

use prison\gangs\objects\{
	Gang
};

class Half{

	public $id;

	public $corner1;
	public $corner2;

	public $arena = null;
	public $gang = null;

	public function __construct(int $id, Vector3 $corner1, Vector3 $corner2){
		$this->id = $id;
		$this->corner1 = $corner1;
		$this->corner2 = $corner2;
	}

	public function getId() : int{
		return $this->id;
	}

	public function getLevel() : ?Level{
		if($this->getArena() === null) return null;
		return $this->getArena()->getLevel();
	}

	public function getCorner1() : Vector3{
		return $this->corner1;
	}

	public function getCorner2() : Vector3{
		return $this->corner2;
	}

	public function getArena() : ?Arena{
		return $this->arena;
	}

	public function setArena(Arena $arena) : void{
		$this->arena = $arena;
	}

	public function getGang() : ?Gang{
		return $this->gang;
	}

	public function hasGang() : bool{
		return $this->getGang() !== null;
	}

	public function setGang(?Gang $gang = null) : void{
		$this->gang = $gang;
	}

	public function getSpawnpoint(int $total, int $number) : Location{
		switch($this->getId()){
			default:
			case 1:
				$yaw = 0;
				break;
			case 2:
				$yaw = 180;
				break;
		}
		$distance = $this->getCorner2()->subtract($this->getCorner1()->getX(), $this->getCorner1()->getY(), $this->getCorner1()->getZ());
		$x = floor($distance->getX());
		$z = abs($distance->getZ()) / 2;

		$between = $x / $total;

		$point = new Location(
			min($this->getCorner1()->getX(),
				max($this->getCorner2()->getX(),
					$this->getCorner1()->getX() + $x - ($between * $number) //annoying math
				)
			), 29, $this->getCorner1()->getZ() + $z, $this->getArena()->getLevel(), $yaw, 0
		);
		return $point;
	}

}