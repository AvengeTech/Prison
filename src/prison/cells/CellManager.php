<?php namespace prison\cells;

use pocketmine\block\Button;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

use prison\Prison;

use core\Core;
use core\user\User;

class CellManager{

	public static int $srid = 0;
	
	public array $display = [];
	public array $corridors = [];

	public array $hcache = [];

	public function __construct(public Cells $main){
		$this->setup();
	}

	public static function newStoreRuntimeId() : int{
		return self::$srid++;
	}

	public function tick() : void{
		foreach($this->getDisplayCells() as $cell){
			$cell->displayTicks++;
			if($cell->displayTicks % 5 == 0){
				$layout = ($lm = $this->getMain()->getLayoutManager())->getRandomLayout();
				if($layout !== null){
					$layout->apply($cell, true, $lm->getFloor($layout->getName()));
				}
			}
		}

		foreach($this->getCorridors() as $corridor){
			$corridor->tick();
		}
	}

	public function close() : void{
		$this->saveAll();
	}

	public function saveAll(bool $async = false) : void{
		foreach($this->getCorridors() as $c){
			foreach($c->getRows() as $r){
				foreach($r->getCells() as $cell){
					$cell->save($async);
				}
			}
		}
	}

	public function doFirstCache() : void{
		foreach($this->getCorridors() as $c){
			foreach($c->getRows() as $r){
				foreach($r->getCells() as $cell){
					foreach($cell->getHolderManager()->getHolders() as $xuid => $holder){
						$this->hcache[$xuid] = $holder;
					}
				}
			}
		}
	}

	public function getHolderCache() : array{
		return $this->hcache;
	}

	public function getMain() : Cells{
		return $this->main;
	}

	public function getDisplayCells() : array{
		return $this->display;
	}

	public function getDisplayCell(int $id) : ?Cell{
		return $this->display[$id] ?? null;
	}

	public function getCorridors() : array{
		return $this->corridors;
	}

	public function getCorridor($corridorId) : ?Corridor{
		return $this->corridors[$corridorId] ?? null;
	}

	public function getRow(int $corridorId, int $rowId) : ?Row{
		$c = $this->getCorridor($corridorId);
		if($c instanceof Corridor){
			return $c->getRow($rowId);
		}
		return null;
	}

	public function getCell(int $corridorId, int $rowId, int $cellId) : ?Cell{
		$r = $this->getRow($corridorId, $rowId);
		if($r instanceof Row){
			return $r->getCell($cellId);
		}
		return null;
	}

	public function getCellByCell(Cell $cell) : ?Cell{
		return $this->getCell($cell->getCorridor(), $cell->getRow(), $cell->getId());
	}

	public function getCellByButton(Button $button) : ?Cell{
		foreach($this->getCorridors() as $corridor){
			if($corridor->inCorridor($button->getPosition())){
				foreach($corridor->getRows() as $row){
					if($row->inRow($button->getPosition())){
						foreach($row->getCells() as $cell){
							if(
								$cell->getStoreButton() === $button ||
								$cell->getQueueButton() === $button
							) return $cell;
						}
						break;
					}
				}
				break;
			}
		}
		return null;
	}

	public function getPlayerCells(Player|User $player, bool $owner = false, bool $fromcache = true) : array{
		$cells = [];
		if($fromcache){
			foreach($this->getHolderCache() as $xuid => $cache){
				if($player->getXuid() == $xuid){
					$cbc = $this->getCellByCell($cache->getCell());
					if(
						$cbc->getHolderManager()->isHolder($player) &&
						(!$owner || $cbc->getHolderManager()->isOwner($player))
					){
						$cells[] = $cbc;
						break;
					}
				}
			}
			return $cells;
		}
		foreach($this->getCorridors() as $corridor){
			foreach($corridor->getRows() as $row){
				foreach($row->getCells() as $cell){
					if(
						$cell->getHolderManager()->isHolder($player) &&
						(!$owner || $cell->getHolderManager()->isOwner($player))
					){
						$cells[] = $cell;
						break;
					}
				}
			}
		}
		return $cells;
	}

	public function getTotalCells(bool $includeDisplay = false) : int{
		$count = 0;
		foreach($this->getCorridors() as $corridor){
			foreach($corridor->getRows() as $row){
				foreach($row->getCells() as $cell){
					$count++;
				}
			}
		}
		if($includeDisplay){
			foreach($this->getDisplayCells() as $cell) $count++;
		}
		return $count;
	}

	public function getCellIn(Player $player) : ?Cell{
		foreach($this->getCorridors() as $corridor){
			if($corridor->inCorridor($player->getPosition())){
				foreach($corridor->getRows() as $row){
					if($row->inRow($player->getPosition())){
						foreach($row->getCells() as $cell){
							if($cell->inCell($player->getPosition())) return $cell;
						}
						break 2;
					}
				}
				break;
			}
		}
		return null;
	}

	public function setup() : void{
		$count = 0;
		foreach(CellData::DISPLAY_CELLS as $key => $data){
			$this->display[$key] = new Cell(
				$key, -1, -1,
				$data["orientation"],
				new Vector3(...$data["corner1"]),
				new Vector3(...$data["corner2"]),
				new Vector3(...$data["entrance"]),
				true, $data["level"] ?? CellData::LEVEL
			);
			$count++;
		}
		Prison::getInstance()->getLogger()->debug("Configured " . $count . " display cells!");

		foreach(CellData::STARTING_CORNERS as $corridor => $data){
			$c = new Corridor($corridor, new Vector3(...CellData::CORRIDOR_CORNERS[$corridor]["corner1"]), new Vector3(...CellData::CORRIDOR_CORNERS[$corridor]["corner2"]), CellData::LEVEL);
			$c->setup();
			$this->corridors[$corridor] = $c;
		}
		Prison::getInstance()->getLogger()->debug("Configured " . $this->getTotalCells(true) . " total cells!");
	}

}