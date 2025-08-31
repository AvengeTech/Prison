<?php namespace prison\cells;

use core\utils\BlockRegistry;
use pocketmine\world\{
	World as Level,
	Position,
};
use pocketmine\entity\Location;

use pocketmine\block\{
	Block,
};
use pocketmine\math\Vector3;
use pocketmine\{block\BlockFactory, block\BlockLegacyIds, block\VanillaBlocks, player\GameMode, player\Player, Server};

use prison\Prison;
use prison\PrisonPlayer;

class Cell{

	const STARTING_RENT = 5000;

	public int $displayTicks = 0;

	public bool $open = true;

	public HolderManager $holderManager;

	public bool $active = false;

	public int $ticks = 0;

	public function __construct(
		public int $id,
		public int $corridor, public int $row, public int $orientation,
		public Vector3 $corner1, public Vector3 $corner2, public Vector3 $entrance,
		public bool $display = false, public string $level = ""
	){
		$this->holderManager = new HolderManager($this);
	}

	public function tick() : bool{
		if(!$this->isActive()) return false;
		$this->ticks++;

		$this->getHolderManager()->tick();

		return $this->isActive();
	}

	public function getName() : string{
		return ($this->isDisplay() ? "DISPLAY " : CellData::getCorridorName($this->getCorridor())) . $this->getId();
	}

	public function getId() : int{
		return $this->id;
	}

	public function getCorridor() : int{
		return $this->corridor;
	}

	public function getRow() : int{
		return $this->row;
	}

	public function getRowObject() : ?Row{
		return Prison::getInstance()->getCells()->getCellManager()->getRow($this->getCorridor(), $this->getRow());
	}

	public function getOrientation() : int{
		return $this->orientation;
	}

	public function getCorner1() : Vector3{
		return $this->corner1;
	}

	public function getCorner2() : Vector3{
		return $this->corner2;
	}

	public function inCell(Position $pos) : bool{
		if($pos->getWorld() !== $this->getLevel()) return false;

		$a = $this->getCorner1();
		$b = $this->getCorner2();

		$p = $pos->asVector3();

		return 
			($a->x <= $p->x && $p->x <= $b->x || $b->x <= $p->x && $p->x <= $a->x) &&
			$a->y <= $p->y && $p->y <= $b->y &&
			($a->z <= $p->z && $p->z <= $b->z || $b->z <= $p->z && $p->z <= $a->z);

	}

	public function getEntrance() : Vector3{
		return $this->entrance;
	}

	public function getLevel() : ?Level{
		if(!$this->isDisplay())
			return $this->getRowObject()->getLevel();

		return Server::getInstance()->getWorldManager()->getWorldByName($this->level);
	}

	public function getFrontOfEntrance() : Vector3{
		return $this->getEntrance()->add($this->getOrientation() == CellData::ORIENTATION_LEFT ? 3 : -3, 0, 0);
	}

	public function getStoreButton() : ?Block{
		return $this->getLevel()->getBlock($this->getEntrance()->add($this->getOrientation() == CellData::ORIENTATION_LEFT ? 1 : -1, 1, $this->getOrientation() == CellData::ORIENTATION_LEFT ? 1 : -1));
	}

	public function getQueueButton() : ?Block{
		return $this->getLevel()->getBlock($this->getEntrance()->add($this->getOrientation() == CellData::ORIENTATION_LEFT ? 1 : -1, 1, $this->getOrientation() == CellData::ORIENTATION_LEFT ? -1 : 1));
	}

	public function gotoFront(Player $player): bool {
		/** @var PrisonPlayer $player */
		if(($ms = $player->getGameSession()->getMines())->inMine()){
			$ms->exitMine(false);
		}elseif($player->isBattleSpectator()){
			$player->stopSpectating();
		}
		$player->teleport(Location::fromObject($this->getFrontOfEntrance(), $this->getLevel(), $this->getOrientation() == CellData::ORIENTATION_LEFT ? 90 : -90));
		$player->setGamemode(GameMode::ADVENTURE());
		return true;
	}

	public function isOpen() : bool{
		return $this->open;
	}

	public function getDoorFace() : int{
		//return $this->getOrientation() == CellData::ORIENTATION_LEFT ? 1 : 0;
		return $this->getOrientation() == CellData::ORIENTATION_LEFT ? 3 : 2;
	}

	public function setOpen(bool $open = true) : void{
		$this->open = $open;
		
		$entrance = $this->getEntrance();
		$this->getLevel()->setBlock($entrance, (
				$open ? VanillaBlocks::AIR() : BlockRegistry::IRON_TRAPDOOR()->setFacing($this->getDoorFace() | 0x04))
		);
	}

	public function canOpen(Player $player) : bool{
		return false;
	}

	public function isDisplay() : bool{
		return $this->display;
	}

	public function getHolderManager() : HolderManager{
		return $this->holderManager;
	}

	public function isHolder($player) : bool{
		return $this->getHolderManager()->isHolder($player);
	}

	public function getHolderBy($player) : ?CellHolder{
		return $this->getHolderManager()->getHolderBy($player);
	}

	public function getOwner() : ?CellHolder{
		return $this->getHolderManager()->getOwner();
	}

	public function isOwner($player) : bool{
		return $this->getHolderManager()->isOwner($player);
	}

	public function claim(Player $player) : void {
		/** @var PrisonPlayer $player */
		$holder = new CellHolder($player->getUser(), $this, true, $this->getNewExpiration());
		$holder->setChanged();
		$this->getHolderManager()->addHolder($holder);
		$this->setActive();

		$player->takeTechits($this->getStartingRent());
	}

	public function isActive() : bool{
		return $this->active;
	}

	public function setActive(bool $active = true) : void{
		$this->active = $active;
	}

	public function getStartingRent() : int{
		return self::STARTING_RENT;
	}

	public function getNewExpiration() : int{
		return (empty($this->getHolderManager()->getHolders()) ? time() : $this->getHolderManager()->getLatestQueued()->getExpiration()) + (86400 * 7);
	}

	public function clear(bool $floor = false) : void{
		$corner1 = $this->getCorner1();
		$corner2 = $this->getCorner2();

		$xx = 0;
		$c1d = clone $corner1;
		while((
			($xb = $corner1->getX() > $corner2->getX()) ? 
			($c1d->getX() >= $corner2->getX()) :
			($c1d->getX() <= $corner2->getX())
		)){
			$yy = 0;
			while($c1d->getY() <= $corner2->getY()){
				$zz = 0;
				while((
					($zb = $corner1->getZ() > $corner2->getZ()) ? 
					($c1d->getZ() >= $corner2->getZ()) :
					($c1d->getZ() <= $corner2->getZ())
				)){
					$block = $this->getLevel()->getBlock($c1d);
					if ($block instanceof Block && $block->getTypeId() !== VanillaBlocks::AIR()->getTypeId()) {
						$this->getLevel()->setBlock($c1d, VanillaBlocks::AIR());
					}
					if($xb){
						$c1d = $c1d->subtract(0, 0, 1);
					}else{
						$c1d = $c1d->add(0, 0, 1);
					}
					$zz++;
				}
				$c1d->z = $corner1->z;
				$c1d = $c1d->add(0, 1, 0);
				$yy++;
			}
			$c1d->y = $corner1->y;
			if($xb){
				$c1d = $c1d->subtract(1, 0, 0);
			}else{
				$c1d = $c1d->add(1, 0, 0);
			}
			$xx++;
		}

		if($floor) $this->clearFloor();
	}

	public function clearFloor() : void{
		$corner1 = $this->getCorner1();
		$corner2 = $this->getCorner2();

		$xx = 0;
		$c1d = clone $corner1; $c1d = $c1d->subtract(0, 1, 0);
		while((
			($xb = $corner1->getX() > $corner2->getX()) ? 
			($c1d->getX() >= $corner2->getX()) :
			($c1d->getX() <= $corner2->getX())
		)){
			$zz = 0;
			while((
				($zb = $corner1->getZ() > $corner2->getZ()) ? 
				($c1d->getZ() >= $corner2->getZ()) :
				($c1d->getZ() <= $corner2->getZ())
			)){
				$block = VanillaBlocks::STONE();
				$b = $this->getLevel()->getBlock($c1d);
				if($b->getTypeId() != $block->getTypeId()){
					$this->getLevel()->setBlock($c1d, $block);
					Prison::getInstance()->getLogger()->debug("Reset floor block [" . $xx . ":" . $zz . "] " . $block->getName() . " from cell: " . $this->getName());
				}
				if($xb){
					$c1d = $c1d->subtract(0, 0, 1);
				}else{
					$c1d = $c1d->add(0, 0, 1);
				}
				$zz++;
			}
			$c1d->z = $corner1->z;

			if($xb){
				$c1d = $c1d->subtract(1, 0, 0);
			}else{
				$c1d = $c1d->add(1, 0, 0);
			}
			$xx++;
		}
	}

	public function save(bool $async = false) : void{
		$this->getHolderManager()->save($async);
	}

}