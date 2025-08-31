<?php namespace prison\cells;

use pocketmine\world\{
	World as Level,
	Position
};
use pocketmine\math\Vector3;
use pocketmine\{
	Server
};

class Corridor{

	public $id;

	public $corner1;
	public $corner2;
	public $level;

	public $rows = [];

	public $active = true;

	public function __construct(int $id, Vector3 $corner1, Vector3 $corner2, string $levelname){
		$this->id = $id;

		$this->corner1 = $corner1;
		$this->corner2 = $corner2;
		$this->level = $levelname;
	}

	public function tick() : bool{
		if(!$this->isActive()){
			if(!empty($this->getActiveRows()))
				$this->setActive();

			return false;
		}
		if(empty(($rows = $this->getActiveRows()))){
			$this->setActive(false);
			return false;
		}

		foreach($rows as $row)
			$row->tick();
		
		return $this->isActive();
	}

	public function setup() : void{
		foreach(CellData::STARTING_CORNERS[($cid = $this->getId())] as $row => $data){
			$r = new Row($row, $cid, new Vector3(...CellData::ROW_CORNERS[$cid][$row]["corner1"]), new Vector3(...CellData::ROW_CORNERS[$cid][$row]["corner2"]));
			$r->setup();
			$this->rows[$row] = $r;
		}
	}

	public function getId() : int{
		return $this->id;
	}

	public function getName() : string{
		return CellData::getCorridorName($this->getId());
	}

	public function getCorner1() : Vector3{
		return $this->corner1;
	}

	public function getCorner2() : Vector3{
		return $this->corner2;
	}

	public function getLevel() : ?Level{
		return Server::getInstance()->getWorldManager()->getWorldByName($this->getLevelName());
	}

	public function getLevelName() : string{
		return $this->level;
	}

	public function inCorridor(Position $pos) : bool{
		if($pos->getWorld() !== $this->getLevel()) return false;

		$a = $this->getCorner1();
		$b = $this->getCorner2();

		$p = $pos->asVector3();

		return 
			($a->x <= $p->x && $p->x <= $b->x || $b->x <= $p->x && $p->x <= $a->x) &&
			$a->y <= $p->y && $p->y <= $b->y &&
			($a->z <= $p->z && $p->z <= $b->z || $b->z <= $p->z && $p->z <= $a->z);
	}

	public function getRows() : array{
		return $this->rows;
	}

	public function getActiveRows() : array{
		$rows = [];
		foreach($this->getRows() as $row){
			if($row->isActive()) $rows[] = $row;
		}
		return $rows;
	}

	public function getRow(int $rowId) : ?Row{
		return $this->rows[$rowId] ?? null;
	}

	public function getCell(int $rowId, int $cellId) : ?Cell{
		$r = $this->getRow($rowId);
		if($r instanceof Row){
			return $r->getCell($cellId);
		}
		return null;
	}

	public function isActive() : bool{
		return $this->active;
	}

	public function setActive(bool $active = true) : void{
		$this->active = $active;
	}

}