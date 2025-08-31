<?php namespace prison\guards;

use pocketmine\math\Vector3;

class Point{

	public $id;

	public $start;
	public $end;

	public function __construct(int $id, Vector3 $start, Vector3 $end){
		$this->id = $id;

		$this->start = $start;
		$this->end = $end;
	}

	public function getId() : int{
		return $this->id;
	}

	public function getStart() : Vector3{
		return $this->start;
	}

	public function getEnd() : Vector3{
		return $this->end;
	}

	public function atEnd(Vector3 $pos) : bool{
		return $this->getEnd()->distance($pos) < 0.5;
	}

}