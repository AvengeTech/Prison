<?php namespace prison\gangs\battle\arena;

use pocketmine\math\Vector3;

class ArenaData{

	const LEVEL_NAME = "garenas";

	const X_START = 0;
	const X_LEN = -48;

	const X_BETWEEN = -5;

	const Y_MIN = 23;
	const Y_MAX = 62;

	const Z_START = 1;
	const Z_LEN = 88;

	const Z_BETWEEN = 5;

	const ROW_LENGTH = 5;
	const TOTAL_ROWS = 2;

	const MIDDLE_WIDTH = 1;

	public static function genArenaData() : array{
		$entries = [];
		$ymin = self::Y_MIN;
		$ymax = self::Y_MAX;
		$id = 1;

		$z = self::Z_START;
		for($row = 1; $row <= self::TOTAL_ROWS; $row++){
			$x = self::X_START;
			for($arenas = 1; $arenas <= self::ROW_LENGTH; $arenas++){
				$entries[$id] = [
					"corner1" => new Vector3($x, $ymin, $z),
					"corner2" => new Vector3($x + self::X_LEN, $ymax, $z + self::Z_LEN),
					"level" => self::LEVEL_NAME,
					"center" => null,
					"halves" => []
				];
				$id++;
				$x = $x + self::X_LEN + self::X_BETWEEN;
			}
			$z = $z + self::Z_LEN + self::Z_BETWEEN;
		}

		foreach($entries as $id => $entry){
			$corner1 = $entry["corner1"];
			$corner2 = $entry["corner2"];

			$z1 = $corner1->z;
			$z2 = $corner2->z;

			$distanceZ = (($z2 - $z1) / 2);
			$entries[$id]["halves"] = [
				1 => [$corner1, $corner2->subtract(0, 0, floor($distanceZ) + self::MIDDLE_WIDTH)],
				2 => [$corner1->add(0, 0, ceil($distanceZ) + self::MIDDLE_WIDTH), $corner2]
			];
			$entries[$id]["center"] = [
				$corner1->add(0, 0, ceil($distanceZ)), $corner2->subtract(0, 0, floor($distanceZ))
			];
		}

		return $entries;
	}

}