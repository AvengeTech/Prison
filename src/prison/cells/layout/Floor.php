<?php namespace prison\cells\layout;

use pocketmine\block\VanillaBlocks;
use pocketmine\block\Block;

use prison\Prison;
use prison\cells\Cell;

class Floor{

	public function __construct(
		public string $name,
		public array $blocks = [],
		public int $orientation = 0
	){}

	public function getName() : string{
		return $this->name;
	}

	public function getFormattedName() : string{
		return ucwords(str_replace("_", " ", $this->getName()));
	}

	public function getBlocks() : array{
		return $this->blocks;
	}

	public function getBlockString() : string{
		$data = [];

		$blocks = $this->getBlocks();
		foreach($blocks as $key => $block){
			$data[$key] = [
				"id" => $block->getId(),
				"meta" => $block->getMeta()
			];
		}

		return zlib_encode(serialize($data), ZLIB_ENCODING_DEFLATE, 1);
	}

	public function getOrientation() : int{
		return $this->orientation;
	}

	public function apply(Cell $cell) : void{
		$corner1 = $cell->getCorner1();
		$corner2 = $cell->getCorner2();

		$blocks = $this->getBlocks();
		$orientation = $this->getOrientation();
		if($orientation != $cell->getOrientation()){
			$blocks = $this->reorganize($blocks);
		}

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
				$block = $blocks[$xx . ":" . $zz] ?? VanillaBlocks::AIR();
				if($block instanceof Block){
					$b = $cell->getLevel()->getBlock($c1d);
					if($b->getTypeId() != $block->getTypeId()){
						$cell->getLevel()->setBlock($c1d, $block);
						//Prison::getInstance()->getLogger()->debug("Pasted block [" . $xx . ":" . $zz . "] " . $block->getName() . " from floor: " . $this->getName());
					}
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

	public function reorganize(array $blocks) : array{
		$newblocks = [];
		foreach($blocks as $key => $block){
			$xz = explode(":", $key);
			$x = $xz[0]; $z = $xz[1];
			$newblocks[$x . ":" . (9 - $z)] = $block;
		}
		return $newblocks;
	}

	public function save() : void{
		$name = $this->getName();
		$blocks = $this->getBlockString();
		$orientation = $this->getOrientation();

		$db = Prison::getInstance()->getSessionManager()->getDatabase();
		$stmt = $db->prepare("
			INSERT INTO cell_floor_data(
				name, blocks, orientation
			) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE
				blocks=VALUES(blocks),
				orientation=VALUES(orientation)
		");

		$stmt->bind_param("ssi", $name, $blocks, $orientation);
		$stmt->execute();
		$stmt->close();
	}

	public function delete() : void{
		$name = $this->getName();

		$db = Prison::getInstance()->getSessionManager()->getDatabase();
		$stmt = $db->prepare("DELETE FROM cell_floor_data WHERE name=?");
		$stmt->bind_param("s", $name);
		$stmt->execute();
		$stmt->close();
	}

}
