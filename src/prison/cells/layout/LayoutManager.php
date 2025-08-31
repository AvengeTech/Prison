<?php namespace prison\cells\layout;

use core\utils\BlockRegistry;
use core\utils\conversion\LegacyBlockIds;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\player\Player;

use prison\Prison;
use prison\cells\{
	Cells,
	Cell
};

class LayoutManager{

	const LAYOUT_COOLDOWN = 60;

	public array $layouts = [];
	public array $floors = [];

	public array $cooldown = [];

	public function __construct(public Cells $cells){
		$this->load();
	}

	public function close() : void{
		//$this->save();
	}

	public function getLayouts(bool $examples = true) : array{
		if($examples) return $this->layouts;
		$layouts = $this->layouts;
		foreach($layouts as $key => $layout){
			if(stristr($layout->getName(), "example")) unset($layouts[$key]);
		}
		return $layouts;
	}

	public function getLayoutNames(bool $examples = false) : array{
		$layouts = [];
		foreach($this->getLayouts($examples) as $layout){
			$layouts[] = $layout->getName();
		}
		return $layouts;
	}

	public function getLayout(string $layoutName) : ?Layout{
		return $this->layouts[strtolower($layoutName)] ?? null;
	}

	public function getRandomLayout(array $choices = []) : ?Layout{
		if(empty($choices)){
			if(empty($this->layouts))
				return null;
			return $this->layouts[array_rand($this->layouts)] ?? null;
		}

		return $choices[array_rand($choices)] ?? null;
	}

	public function createLayoutFrom(Cell $cell, string $name = "cell layout", string $description = "yoooo", int $level = 1, string $floorName = "") : Layout{
		$corner1 = $cell->getCorner1();
		$corner2 = $cell->getCorner2();
		$blocks = [];
		$orientation = $cell->getOrientation();

		Prison::getInstance()->getLogger()->debug("Checking blocks for new layout: " . $name);
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
					$block = $cell->getLevel()->getBlock($c1d);
					if($block instanceof Block && $block->getTypeId() != LegacyBlockIds::legacyIdToTypeId(0)){
						$blocks[$xx . ":" . $yy . ":" . $zz] = $block;
						Prison::getInstance()->getLogger()->debug("Copied block [" . $xx . ":" . $yy . ":" . $zz . "] " . $block->getName() . " to new layout: " . $name);
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

		if($floorName !== "") $this->createFloorFrom($cell, $floorName);
		$layout = $this->layouts[$name] = new Layout($name, $description, $blocks, $orientation, $level, $floorName);
		$layout->save();
		return $layout;
	}

	public function removeLayout(string $name, bool $perma = true) : bool{
		if(($layout = $this->getLayout($name)) == null)
			return false;

		unset($this->layouts[$name]);
		if($perma) $layout->delete();
		return true;
	}

	public function getFloors(bool $examples = true) : array{
		if($examples) return $this->floors;
		$floors = $this->floors;
		foreach($floors as $key => $floor){
			if(stristr($floor->getName(), "example")) unset($floors[$key]);
		}
		return $floors;
	}

	public function getFloorNames(bool $examples = false) : array{
		$floors = [];
		foreach($this->getFloors($examples) as $floor){
			$floors[] = $floor->getName();
		}
		return $floors;
	}

	public function getFloor(string $floorName) : ?Floor{
		return $this->floors[strtolower($floorName)] ?? null;
	}

	public function getDefaultFloor() : ?Floor{
		return null; //todo
	}

	public function createFloorFrom(Cell $cell, string $name) : Floor{
		$corner1 = $cell->getCorner1();
		$corner2 = $cell->getCorner2();
		$blocks = [];
		$orientation = $cell->getOrientation();

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
				$block = $cell->getLevel()->getBlock($c1d);
				if($block instanceof Block && $block->getTypeId() != LegacyBlockIds::legacyIdToTypeId(0)){
					$blocks[$xx . ":" . $zz] = $block;
					Prison::getInstance()->getLogger()->debug("Copied block [" . $xx . ":" . $zz . "] " . $block->getName() . " to new floor: " . $name);
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

		$floor = $this->floors[$name] = new Floor($name, $blocks, $orientation);
		$floor->save();
		return $floor;
	}

	public function removeFloor(string $name, bool $perma = true) : bool{
		if(($floor = $this->getFloor($name)) == null)
			return false;

		unset($this->floors[$name]);
		if($perma) $floor->delete();
		return true;
	}


	public function load() : void{
		$db = Prison::getInstance()->getSessionManager()->getDatabase();

		$floors = [];
		$stmt = $db->prepare("SELECT * FROM cell_floor_data");
		$stmt->bind_result($name, $blocks, $orientation);
		if($stmt->execute()){
			while($stmt->fetch()){
				$floors[$name] = new Floor($name, $this->parseBlocks($blocks), $orientation);
			}
		}
		$stmt->close();

		usort($floors, function($a, $b){
			return strnatcmp($a->getName(), $b->getName());
		});
		foreach($floors as $floor)
			$this->floors[$floor->getName()] = $floor;

		$layouts = [];
		$stmt = $db->prepare("SELECT * FROM cell_layout_data");
		$stmt->bind_result($name, $description, $blocks, $orientation, $level, $floor);
		if($stmt->execute()){
			while($stmt->fetch()){
				$layouts[$name] = new Layout($name, $description, $this->parseBlocks($blocks), $orientation, $level, $floor);
			}
		}

		usort($layouts, function($a, $b){
			return strnatcmp($a->getName(), $b->getName());
		});
		foreach($layouts as $layout)
			$this->layouts[$layout->getName()] = $layout;

		$stmt->close();
	}

	public function parseBlocks(string $data) : array{
		return [];
		$data = unserialize(zlib_decode($data));
		$blocks = [];
		foreach($data as $key => $bd){
			$blocks[$key] = BlockRegistry::getBlockById(LegacyBlockIds::legacyIdToTypeId($bd["id"], $bd["meta"]), -1);
		}
		return $blocks;
	}

	public function save() : void{
		$db = Prison::getInstance()->getSessionManager()->getDatabase();
		$stmt = $db->prepare("
			INSERT INTO cell_floor_data(
				name, blocks, orientation
			) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE
				blocks=VALUES(blocks),
				orientation=VALUES(orientation)
		");
		foreach($this->getFloors() as $floor){
			$name = $floor->getName();
			$blocks = $floor->getBlockString();
			$orientation = $floor->getOrientation();

			$stmt->bind_param("ssi", $name, $blocks, $orientation);
			$stmt->execute();
		}
		$stmt->close();

		$stmt = $db->prepare("
			INSERT INTO cell_layout_data(
				name, description, blocks, orientation, level, floor
			) VALUES(?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
				description=VALUES(blocks),
				blocks=VALUES(blocks),
				orientation=VALUES(orientation),
				level=VALUES(level),
				floor=VALUES(floor)
		");
		foreach($this->getLayouts() as $layout){
			$name = $layout->getName();
			$description = $layout->getDescription();
			$blocks = $layout->getBlockString();
			$orientation = $layout->getOrientation();
			$level = $layout->getRequiredLevel();
			$floor = $layout->getFloorName();

			$stmt->bind_param("sssiis", $name, $description, $blocks, $orientation, $level, $floor);
			$stmt->execute();
		}
		$stmt->close();
	}

	public function hasCooldown(Player $player) : bool{
		return $this->getCooldown($player) > 0;
	}

	public function getCooldown(Player $player) : int{
		return ($this->cooldown[$player->getName()] ?? 0) - time();
	}

	public function setCooldown(Player $player) : void{
		$this->cooldown[$player->getName()] = time() + self::LAYOUT_COOLDOWN;
	}

}