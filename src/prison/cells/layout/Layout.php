<?php

namespace prison\cells\layout;

use core\utils\BlockRegistry;
use core\utils\conversion\LegacyBlockIds;
use pocketmine\block\BlockFactory;
use pocketmine\block\VanillaBlocks;
use pocketmine\Server;
use pocketmine\world\World as Level;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\Button;
use pocketmine\block\Stair;
use pocketmine\block\Trapdoor;
use pocketmine\block\WallSign;
use pocketmine\data\bedrock\block\BlockTypeNames;
use prison\Prison;
use prison\cells\Cell;

class Layout {

	public function __construct(
		public string $name,
		public string $description = "",

		public array $blocks = [],
		public int $orientation = 0,
		public int $level = 1,

		public string $floor = ""
	) {
	}

	public function getName(): string {
		return $this->name;
	}

	public function getFormattedName(): string {
		return ucwords(str_replace("_", " ", $this->getName()));
	}

	public function getDescription(): string {
		return $this->description;
	}

	public function getBlocks(): array {
		return $this->blocks;
	}

	public function getBlockString(): string {
		$data = [];

		$blocks = $this->getBlocks();
		foreach ($blocks as $key => $block) {
			$data[$key] = [
				"id" => $block->getId(),
				"meta" => $block->getMeta()
			];
		}

		return zlib_encode(serialize($data), ZLIB_ENCODING_DEFLATE, 1);
	}

	public function getOrientation(): int {
		return $this->orientation;
	}

	public function getRequiredLevel(): int {
		return $this->level;
	}

	public function getFloorName(): string {
		return $this->floor;
	}

	public function getFloor(): ?Floor {
		return ($lm = Prison::getInstance()->getCells()->getLayoutManager())->getFloor($this->getName()) ?? $lm->getDefaultFloor();
	}

	public function getLevel(): ?Level {
		return Server::getInstance()->getWorldManager()->getWorldByName("newpsn");
	}

	public function clear(Cell $cell): void {
		$cell->clear();
	}

	public function apply(Cell $cell, bool $updateFloor = false, ?Floor $floor = null): void {
		if ($updateFloor) {
			if ($floor == null)
				$floor = $this->getFloor();
			if ($floor != null)
				$floor->apply($cell);
		}

		$corner1 = $cell->getCorner1();
		$corner2 = $cell->getCorner2();

		$blocks = $this->getBlocks();

		$o = $cell->getOrientation();
		if ($o != $this->getOrientation())
			$blocks = $this->reorganize($this->reorient($blocks));


		$doLast = [];
		$lastIds = [
			BlockTypeIds::LADDER,
			BlockTypeIds::TORCH,
		];

		$xx = 0;
		$c1d = clone $corner1;
		while ((
			($xb = $corner1->getX() > $corner2->getX()) ?
			($c1d->getX() >= $corner2->getX()) : ($c1d->getX() <= $corner2->getX())
		)) {
			$yy = 0;
			while ($c1d->getY() <= $corner2->getY()) {
				$zz = 0;
				while ((
					($zb = $corner1->getZ() > $corner2->getZ()) ?
					($c1d->getZ() >= $corner2->getZ()) : ($c1d->getZ() <= $corner2->getZ())
				)) {
					$block = $blocks[$xx . ":" . $yy . ":" . $zz] ?? VanillaBlocks::AIR();
					if ($block instanceof Block) {
						$b = $this->getLevel()->getBlock($c1d);
						if ($block->getTypeId() != VanillaBlocks::AIR()->getTypeId() || $b->getTypeId() !== VanillaBlocks::AIR()->getTypeId()) { //replace blocks if necessary?
							if (!in_array($block->getTypeId(), $lastIds) || $block instanceof WallSign) {
								$cell->getLevel()->setBlock($c1d, $block);
							} else {
								$doLast[] = [$c1d, $block];
							}
							//Prison::getInstance()->getLogger()->debug("Placed block [" . $xx . ":" . $yy . ":" . $zz . "] " . $block->getName() . " from layout: " . $this->getName());
						}
					}
					if ($xb) {
						$c1d = $c1d->subtract(0, 0, 1);
					} else {
						$c1d = $c1d->add(0, 0, 1);
					}
					$zz++;
				}
				$c1d->z = $corner1->z;
				$c1d = $c1d->add(0, 1, 0);
				$yy++;
			}
			$c1d->y = $corner1->y;
			if ($xb) {
				$c1d = $c1d->subtract(1, 0, 0);
			} else {
				$c1d = $c1d->add(1, 0, 0);
			}
			$xx++;
		}

		foreach ($doLast as $dl) {
			$cell->getLevel()->setBlock($dl[0], $dl[1]);
		}
	}

	/** @param Block[] $blocks */
	public function reorient(array $blocks): array {
		foreach ($blocks as $key => $block) {
			$meta = LegacyBlockIds::stateIdToMeta($block->getStateId());
			switch (($id = $block->getTypeId())) {
				default:
					$newblocks[$key] = $block;
					break;

				case BlockTypeIds::CHEST:

				case BlockTypeIds::GLAZED_TERRACOTTA:
					if ($meta == 2) {
						$meta = 3;
					} elseif ($meta == 3) {
						$meta = 2;
					} elseif ($meta == 4) {
						$meta = 5;
					} elseif ($meta == 5) {
						$meta = 4;
					}

					$blocks[$key] = BlockRegistry::getBlockById($id, $meta);
					Prison::getInstance()->getLogger()->debug("Flipped block [" . $key . "] " . $block->getName() . " from layout: " . $this->getName());
					break;

				case BlockTypeIds::OAK_DOOR:
				case BlockTypeIds::SPRUCE_DOOR:
				case BlockTypeIds::BIRCH_DOOR:
				case BlockTypeIds::JUNGLE_DOOR:
				case BlockTypeIds::ACACIA_DOOR:
				case BlockTypeIds::DARK_OAK_DOOR:
				case BlockTypeIds::IRON_DOOR:
					if ($meta == 2) {
						$meta = 0;
					} elseif ($meta == 0) {
						$meta = 2;
					} elseif ($meta == 1) {
						$meta = 3;
					} elseif ($meta == 3) {
						$meta = 1;
					} elseif ($meta == 4) {
						$meta = 6;
					} elseif ($meta == 6) {
						$meta = 4;
					} elseif ($meta == 5) {
						$meta = 7;
					} elseif ($meta == 7) {
						$meta = 5;
					}

					$blocks[$key] = BlockRegistry::getBlockById($id, $meta);
					Prison::getInstance()->getLogger()->debug("Flipped block [" . $key . "] " . $block->getName() . " from layout: " . $this->getName());
					break;

				case $block->getTypeId():
					if ($block instanceof Stair) {
						if ($meta == 0) {
							$meta = 1;
						} elseif ($meta == 1) {
							$meta = 0;
						}
						/**elseif($meta == 2){
						$meta = 3;
					}elseif($meta == 3){
						$meta = 2;
					}elseif($meta == 4){
						$meta = 5;
					}elseif($meta == 5){
						$meta = 4;
					}elseif($meta == 6){
						$meta = 7;
					}elseif($meta == 7){
						$meta = 6;
					}*/
						$blocks[$key] = BlockRegistry::getBlockById($id, $meta);
						Prison::getInstance()->getLogger()->debug("Flipped block [" . $key . "] " . $block->getName() . " from layout: " . $this->getName());
					} elseif ($block instanceof Button) {
						/**if($meta == 2){
						$meta = 3;
					}elseif($meta == 3){
						$meta = 2;
					}else*/ if ($meta == 4) {
							$meta = 5;
						} elseif ($meta == 5) {
							$meta = 4;
						}
						$blocks[$key] = BlockRegistry::getBlockById($id, $meta);
						Prison::getInstance()->getLogger()->debug("Flipped block [" . $key . "] " . $block->getName() . " from layout: " . $this->getName());
					} elseif ($block instanceof Trapdoor) {
						if ($meta == 1) {
							$meta = 0;
						} elseif ($meta == 0) {
							$meta = 1;
						} elseif ($meta == 2) {
							$meta = 3;
						} elseif ($meta == 3) {
							$meta = 2;
						} elseif ($meta == 8) {
							$meta = 9;
						} elseif ($meta == 9) {
							$meta = 8;
						} elseif ($meta == 10) {
							$meta = 11;
						} elseif ($meta == 11) {
							$meta = 10;
						} elseif ($meta == 13) {
							$meta = 12;
						} elseif ($meta == 12) {
							$meta = 13;
						}


						$blocks[$key] = BlockRegistry::getBlockById($id, $meta);
						Prison::getInstance()->getLogger()->debug("Flipped block [" . $key . "] " . $block->getName() . " from layout: " . $this->getName());
					} elseif ($block instanceof WallSign) {
						if ($meta == 2) {
							$meta = 3;
						} elseif ($meta == 3) {
							$meta = 2;
						} elseif ($meta == 4) {
							$meta = 5;
						} elseif ($meta == 5) {
							$meta = 4;
						}
						$blocks[$key] = BlockRegistry::getBlockById($id, $meta);
						Prison::getInstance()->getLogger()->debug("Flipped block [" . $key . "] " . $block->getName() . " from layout: " . $this->getName());
					}
					break;

				case BlockTypeIds::LADDER:
					/**if($meta == 2){
						$meta = 3;
					}elseif($meta == 3){
						$meta = 2;
					}else*/ if ($meta == 4) {
						$meta = 5;
					} elseif ($meta == 5) {
						$meta = 4;
					}
					$blocks[$key] = BlockRegistry::getBlockById($id, $meta);
					Prison::getInstance()->getLogger()->debug("Flipped block [" . $key . "] " . $block->getName() . " from layout: " . $this->getName());
					break;

				case BlockTypeIds::VINES:
					if ($meta == 1) {
						$meta = 4;
					} elseif ($meta == 4) {
						$meta = 1;
					} elseif ($meta == 2) {
						$meta = 8;
					} elseif ($meta == 8) {
						$meta = 2;
					}

					$blocks[$key] = BlockRegistry::getBlockById($id, $meta);
					Prison::getInstance()->getLogger()->debug("Flipped block [" . $key . "] " . $block->getName() . " from layout: " . $this->getName());
					break;
			}
		}
		return $blocks;
	}

	public function reorganize(array $blocks): array {
		$newblocks = [];
		foreach ($blocks as $key => $block) {
			$xyz = explode(":", $key);
			$x = $xyz[0];
			$y = $xyz[1];
			$z = $xyz[2];
			$newblocks[$x . ":" . $y . ":" . (9 - $z)] = $block;
		}
		return $newblocks;
	}

	public function save(): void {
		$name = $this->getName();
		$description = $this->getDescription();
		$blocks = $this->getBlockString();
		$orientation = $this->getOrientation();
		$level = $this->getRequiredLevel();
		$floor = $this->getFloorName();

		$db = Prison::getInstance()->getSessionManager()->getDatabase();
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
		$stmt->bind_param("sssiis", $name, $description, $blocks, $orientation, $level, $floor);
		$stmt->execute();
		$stmt->close();
	}


	public function delete(): void {
		$name = $this->getName();

		$db = Prison::getInstance()->getDatabase();
		$stmt = $db->prepare("DELETE FROM cell_layout_data WHERE name=?");
		$stmt->bind_param("s", $name);
		$stmt->execute();
		$stmt->close();
	}
}
