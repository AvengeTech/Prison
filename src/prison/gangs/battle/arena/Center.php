<?php namespace prison\gangs\battle\arena;

use pocketmine\math\Vector3;
use pocketmine\world\World;

class Center{

	public ?Arena $arena = null;

	public function __construct(
		public Vector3 $corner1,
		public Vector3 $corner2
	){}

	public function getArena() : ?Arena{
		return $this->arena;
	}

	public function setArena(Arena $arena) : void{
		$this->arena = $arena;
	}

	public function getLevel() : ?World{
		if($this->getArena() === null) return null;
		return $this->getArena()->getLevel();
	}

	public function getCorner1() : Vector3{
		return $this->corner1;
	}

	public function getCorner2() : Vector3{
		return $this->corner2;
	}

	public function getDot() : Vector3{
		return $dot = $this->getCorner1()->add(-25, 6, 0); //HACKY! todo: get exact value
	}

}