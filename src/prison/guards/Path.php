<?php namespace prison\guards;

use pocketmine\math\Vector3;

class Path{

	public $name;

	public $points = [];

	public $loops;

	public $started = false;
	public $currentPoint = null;

	public function __construct(string $name, array $points = [], bool $loops = false){
		$this->name = $name;

		$this->points = $points;

		$this->loops = $loops;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getStartingPoint() : Point{
		return $this->getPoint(0);
	}

	public function getPoints() : array{
		return $this->points;
	}

	public function getPoint(int $id) : ?Point{
		foreach($this->getPoints() as $point){
			if($point->getId() == $id) return $point;
		}
		return null;
	}

	public function getEndingPoint() : Point{
		return array_reduce($this->getPoints(), function($a, $b){
			return $a ? ($a->getId() > $b->getId() ? $a : $b) : $b;
		});
	}

	public function doesLoop() : bool{
		return $this->loops;
	}

	public function isStarted() : bool{
		return $this->started;
	}

	public function setStarted(bool $started = true) : void{
		$this->started = $started;
		$this->setCurrentPoint($started ? $this->getStartingPoint() : null);
	}

	public function getCurrentPoint() : ?Point{
		return $this->currentPoint;
	}

	public function setCurrentPoint(?Point $point = null) : void{
		$this->currentPoint = $point;
	}

	public function atPointEnd(Vector3 $pos) : bool{
		if(!$this->isStarted()) return false;
		$end = $this->getCurrentPoint()->atEnd($pos);
		//var_dump($end);
		return $end;
	}

	public function getNextPoint() : ?Point{
		$cp = $this->getCurrentPoint();
		if($cp == null) return $this->getStartingPoint();

		if(($p = $this->getPoint($cp->getId() + 1)) == null && $this->doesLoop()){
			return $this->getStartingPoint();
		}

		return $p;
	}

	public function save() : bool{
		$data = [
			"loops" => $this->doesLoop(),
			"points" => [],
		];
		$id = 0;
		foreach($this->getPoints() as $point){
			$v = $point->getStart()->subtract(0.5, 0, 0.5);
			$data["points"][$id] = [$v->x, $v->y, $v->z];
			$id++;
		}
		if(!$data["loops"]){
			$p = $this->getEndingPoint();
			$data["points"][$p->getId() + 1] = [
				($e = $p->getEnd()->subtract(0.5, 0, 0.5))->x,
				$e->y,
				$e->z
			];
		}

		if(!file_exists(($file = PathManager::DIRECTORY . $this->getName() . ".json"))){
			file_put_contents($file, json_encode($data));
			return true;
		}
		return false;
	}

}