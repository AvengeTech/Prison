<?php namespace prison\cells;

class CellData{

	const LEVEL = "newpsn";

	const ROW_BOTTOM_LEFT = 1;
	const ROW_BOTTOM_RIGHT = 2;
	const ROW_TOP_LEFT = 3;
	const ROW_TOP_RIGHT = 4;

	const ORIENTATION_LEFT = 0;
	const ORIENTATION_RIGHT = 1;

	const CORRIDOR_A = 1;
	const CORRIDOR_B = 2;
	const CORRIDOR_C = 3;

	const ROW_LENGTH = 10;

	const DISPLAY_CELLS = [
		1 => [
			"corner1" => [-948,25,335],
			"corner2" => [-957,31,326],
			"orientation" => self::ORIENTATION_LEFT,
			"entrance" => [-947,25,333],
		]
	];

	const ROW_CORNERS = [
		self::CORRIDOR_A => [
			self::ROW_BOTTOM_LEFT => [
				"corner1" => [-848,24,320],
				"corner2" => [-866,30,210],
			],
			self::ROW_BOTTOM_RIGHT => [
				"corner1" => [-838,24,320],
				"corner2" => [-820,30,210],
			],
			self::ROW_TOP_LEFT => [
				"corner1" => [-848,34,320],
				"corner2" => [-866,40,210],
			],
			self::ROW_TOP_RIGHT => [
				"corner1" => [-838,34,320],
				"corner2" => [-820,40,210],
			],
		],
		self::CORRIDOR_B => [
			self::ROW_BOTTOM_LEFT => [
				"corner1" => [-894,24,320],
				"corner2" => [-912,30,210],
			],
			self::ROW_BOTTOM_RIGHT => [
				"corner1" => [-884,24,320],
				"corner2" => [-866,30,210],
			],
			self::ROW_TOP_LEFT => [
				"corner1" => [-894,34,320],
				"corner2" => [-912,40,210],
			],
			self::ROW_TOP_RIGHT => [
				"corner1" => [-884,34,320],
				"corner2" => [-866,40,210],
			],
		],
		self::CORRIDOR_C => [
			self::ROW_BOTTOM_LEFT => [
				"corner1" => [-940,24,320],
				"corner2" => [-958,30,210],
			],
			self::ROW_BOTTOM_RIGHT => [
				"corner1" => [-930,24,320],
				"corner2" => [-912,30,210],
			],
			self::ROW_TOP_LEFT => [
				"corner1" => [-940,34,320],
				"corner2" => [-958,40,210],
			],
			self::ROW_TOP_RIGHT => [
				"corner1" => [-930,34,320],
				"corner2" => [-912,40,210],
			],
		],
	];

	const CORRIDOR_NAMES = [
		self::CORRIDOR_A => "A",
		self::CORRIDOR_B => "B",
		self::CORRIDOR_C => "C"
	];

	const CORRIDOR_CORNERS = [
		self::CORRIDOR_A => [
			"corner1" => [-866,24,320],
			"corner2" => [-820,44,210],
		],
		self::CORRIDOR_B => [
			"corner1" => [-912,24,320],
			"corner2" => [-866,44,210],
		],
		self::CORRIDOR_C => [
			"corner1" => [-958,24,320],
			"corner2" => [-912,44,210],
		],
	];

	//By row
	const CORNER_DISTANCE_ROW = 11;
	const CORNER_DISTANCE_ABOVE = 10;

	/**
	 * Note: First corner will always
	 * be the left side from where
	 * you're facing
	 */
	const STARTING_CORNERS = [
		self::CORRIDOR_A => [
			self::ROW_BOTTOM_LEFT => [
				"orientation" => self::ORIENTATION_LEFT,
				"corner1" => [-856,25,319],
				"corner2" => [-865,31,310],
				"entrance" => [-855,25,317],
			],
			self::ROW_BOTTOM_RIGHT => [
				"orientation" => self::ORIENTATION_RIGHT,
				"corner1" => [-830,25,310],
				"corner2" => [-821,31,319],
				"entrance" => [-831,25,317],
			],
			self::ROW_TOP_LEFT => [
				"orientation" => self::ORIENTATION_LEFT,
				"corner1" => [-856,35,319],
				"corner2" => [-865,41,310],
				"entrance" => [-855,35,317],
			],
			self::ROW_TOP_RIGHT => [
				"orientation" => self::ORIENTATION_RIGHT,
				"corner1" => [-830,35,310],
				"corner2" => [-821,41,319],
				"entrance" => [-831,35,317],
			],
		],
		self::CORRIDOR_B => [
			self::ROW_BOTTOM_LEFT => [
				"orientation" => self::ORIENTATION_LEFT,
				"corner1" => [-902,25,319],
				"corner2" => [-911,31,310],
				"entrance" => [-901,25,317],
			],
			self::ROW_BOTTOM_RIGHT => [
				"orientation" => self::ORIENTATION_RIGHT,
				"corner1" => [-876,25,310],
				"corner2" => [-867,31,319],
				"entrance" => [-877,25,317],
			],
			self::ROW_TOP_LEFT => [
				"orientation" => self::ORIENTATION_LEFT,
				"corner1" => [-902,35,319],
				"corner2" => [-911,41,310],
				"entrance" => [-901,35,317],
			],
			self::ROW_TOP_RIGHT => [
				"orientation" => self::ORIENTATION_RIGHT,
				"corner1" => [-876,35,310],
				"corner2" => [-867,41,319],
				"entrance" => [-877,35,317],
			],
		],
		self::CORRIDOR_C => [
			self::ROW_BOTTOM_LEFT => [
				"orientation" => self::ORIENTATION_LEFT,
				"corner1" => [-948,25,319],
				"corner2" => [-957,31,310],
				"entrance" => [-947,25,317],
			],
			self::ROW_BOTTOM_RIGHT => [
				"orientation" => self::ORIENTATION_RIGHT,
				"corner1" => [-922,25,310],
				"corner2" => [-913,31,319],
				"entrance" => [-923,25,317],
			],
			self::ROW_TOP_LEFT => [
				"orientation" => self::ORIENTATION_LEFT,
				"corner1" => [-948,35,319],
				"corner2" => [-957,41,310],
				"entrance" => [-947,35,317],
			],
			self::ROW_TOP_RIGHT => [
				"orientation" => self::ORIENTATION_RIGHT,
				"corner1" => [-922,35,310],
				"corner2" => [-913,41,319],
				"entrance" => [-923,35,317],
			],
		],
	];

	const LAYOUT_BLOCKS = [
		"default" => [
			"description" => "The default cell layout.",
			"blocks" => [

			],
			"stores" => [

			],
		],
	];

	public static function getCorridorName(int $corridor) : string{
		return self::CORRIDOR_NAMES[$corridor] ?? "?";
	}

	public static function getRowData(int $corridor, int $row) : array{
		$cid = 1 + (($row - 1) * 10);
		$cells = [];

		$sd = self::STARTING_CORNERS[$corridor][$row];

		$o = $sd["orientation"];

		$x1 = $sd["corner1"][0];
		$x2 = $sd["corner2"][0];
		$x3 = $sd["entrance"][0];

		$y1 = $sd["corner1"][1];
		$y2 = $sd["corner2"][1];
		$y3 = $sd["entrance"][1];

		$z1 = $sd["corner1"][2];
		$z2 = $sd["corner2"][2];
		$z3 = $sd["entrance"][2];

		$cells[$cid] = $sd;
		while(count($cells) < self::ROW_LENGTH){
			$z1 -= self::CORNER_DISTANCE_ROW;
			$z2 -= self::CORNER_DISTANCE_ROW;
			$z3 -= self::CORNER_DISTANCE_ROW;

			$cid++;
			$cells[$cid] = [
				"orientation" => $o,
				"corner1" => [$x1,$y1,$z1],
				"corner2" => [$x2,$y2,$z2],
				"entrance" => [$x3,$y3,$z3],
			];
		}

		return $cells;
	}


}