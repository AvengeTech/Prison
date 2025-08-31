<?php namespace prison\cells;

use pocketmine\world\{
	World as Level,
	Position
};
use pocketmine\math\Vector3;

use prison\Prison;

class Row{

	public $id;
	public $corridor;

	public $corner1;
	public $corner2;

	public $cells = [];

	public $active = true;

	public function __construct(int $id, int $corridor, Vector3 $corner1, Vector3 $corner2){
		$this->id = $id;
		$this->corridor = $corridor;

		$this->corner1 = $corner1;
		$this->corner2 = $corner2;
	}

	public function tick() : bool{
		if(!$this->isActive()){
			if(!empty($this->getActiveCells()))
				$this->setActive();

			return false;
		}
		if(empty(($cells = $this->getActiveCells()))){
			$this->setActive(false);
			return false;
		}

		foreach($cells as $cell){
			$cell->tick();
		}

		return $this->isActive();
	}

	public function setup() : void{
		foreach(CellData::getRowData(($corridor = $this->getCorridor()), ($rid = $this->getId())) as $cell => $data){
			$this->cells[$cell] = new Cell(
				$cell, $corridor, $rid, $data["orientation"],
				new Vector3(...$data["corner1"]),
				new Vector3(...$data["corner2"]),
				new Vector3(...$data["entrance"])
			);
		}
	}

	public function getId() : int{
		return $this->id;
	}

	public function getCorridor() : int{
		return $this->corridor;
	}

	public function getCorridorObject() : ?Corridor{
		return Prison::getInstance()->getCells()->getCellManager()->getCorridor($this->getCorridor());
	}

	public function getCorner1() : Vector3{
		return $this->corner1;
	}

	public function getCorner2() : Vector3{
		return $this->corner2;
	}

	public function getLevel() : ?Level{
		return $this->getCorridorObject()->getLevel();
	}

	public function inRow(Position $pos) : bool{
		if($pos->getWorld() !== $this->getLevel()) return false;

		$a = $this->getCorner1();
		$b = $this->getCorner2();

		$p = $pos->asVector3();

		return 
			($a->x <= $p->x && $p->x <= $b->x || $b->x <= $p->x && $p->x <= $a->x) &&
			$a->y <= $p->y && $p->y <= $b->y &&
			($a->z <= $p->z && $p->z <= $b->z || $b->z <= $p->z && $p->z <= $a->z);
	}


	public function getCells() : array{
		return $this->cells;
	}

	public function getActiveCells() : array{
		$cells = [];
		foreach($this->getCells() as $cell){
			if($cell->isActive()) $cells[] = $cell;
		}
		return $cells;
	}

	public function getCell(int $cellId) : ?Cell{
		return $this->cells[$cellId] ?? null;
	}

	public function isActive() : bool{
		return $this->active;
	}

	public function setActive(bool $active = true) : void{
		$this->active = $active;
	}

}