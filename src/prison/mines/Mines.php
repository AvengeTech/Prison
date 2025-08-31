<?php

namespace prison\mines;

use core\Core;
use pocketmine\Server;
use pocketmine\math\Vector3;

use prison\Prison;
use prison\mines\commands\MineCommand;

class Mines {

	public array $mines = [];

	public array $inMine = [];

	public function __construct(public Prison $plugin) {
		$this->registerMines();

		$map = $plugin->getServer()->getCommandMap();
		$map->register("mine", new MineCommand($plugin, "mine", "Teleport to a mine!"));
	}

	public function registerMines(): void {
		$isPvP = Core::thisServer()->isSubServer() && Core::thisServer()->getSubId() === "pvp";
		$def = $this->plugin->getServer()->getWorldManager()->getWorldByName("world");
		foreach ([
			"a" => new Mine("a", 5, new Vector3(348.5, 40, 238.5), new Vector3(342, 39, 246), new Vector3(326, 9, 230), $def, ["coal_ore" => 25, "oak_log" => 25, "stone" => 50]),
			"b" => new Mine("b", 5, new Vector3(352.5, 40, 309.5), new Vector3(330, 39, 301), new Vector3(346, 23, 317), $def, ["iron_ore" => 15, "oak_log" => 15, "coal_ore" => 25, "stone" => 50]),
			"c" => new Mine("c", 5, new Vector3(347.5, 40, 384.5), new Vector3(341, 39, 376), new Vector3(325, 23, 392), $def, ["iron_ore" => 50, "coal_ore" => 35, "stone" => 15]),
			"d" => new Mine("d", 5, new Vector3(343.5, 40, 456.5), new Vector3(337, 39, 464), new Vector3(321, 23, 448), $def, ["iron_ore" => 70, "gold_ore" => 10, "coal_ore" => 20]),
			"e" => new Mine("e", 5, new Vector3(337.5, 40, 526.5), new Vector3(331, 39, 518), new Vector3(315, 23, 534), $def, ["iron_block" => 10, "gold_ore" => 15, "coal_ore" => 20, "iron_ore" => 25, "stone" => 15, "red_sand" => 15]),

			"f" => new Mine("f", 8, new Vector3(420.5, 40, 232.5), new Vector3(414, 39, 225), new Vector3(398, 22, 241), $def, ["iron_block" => 15, "gold_ore" => 25, "iron_ore" => 50, "stone" => 10]),
			"g" => new Mine("g", 8, new Vector3(423.5, 40, 305.5), new Vector3(417, 39, 313), new Vector3(401, 23, 297), $def, ["iron_block" => 20, "gold_ore" => 30, "iron_ore" => 45, "stone_bricks" => 5]),
			"h" => new Mine("h", 8, new Vector3(422.5, 40, 385.5), new Vector3(416, 39, 393), new Vector3(400, 23, 377), $def, ["gold_block" => 5, "redstone_block" => 10, "iron_block" => 20, "gold_ore" => 15, "iron_ore" => 45, "snow_block" => 5]),
			"i" => new Mine("i", 8, new Vector3(425.5, 40, 455.5), new Vector3(418, 39, 463), new Vector3(402, 23, 447), $def, ["gold_block" => 10, "redstone_block" => 10, "iron_block" => 15, "gold_ore" => 25, "iron_ore" => 35, "spruce_log" => 5]),
			"j" => new Mine("j", 8, new Vector3(423.5, 40, 523.5), new Vector3(416, 39, 531), new Vector3(400, 23, 515), $def, ["gold_block" => 10, "redstone_block" => 15, "iron_block" => 15, "gold_ore" => 20, "iron_ore" => 30, "jungle_log" => 5, "diamond_ore" => 5]),

			"k" => new Mine("k", 12, new Vector3(491.5, 42, 233.5), new Vector3(486, 41, 241), new Vector3(470, 25, 225), $def, ["iron_block" => 10, "gold_block" => 15, "redstone_block" => 15, "gold_ore" => 20, "iron_ore" => 25, "diamond_ore" => 5, "slime_block" => 5, "blue_stained_clay" => 5]),
			"l" => new Mine("l", 12, new Vector3(492.5, 42, 312.5), new Vector3(487, 41, 320), new Vector3(471, 25, 304), $def, ["iron_block" => 15, "gold_block" => 15, "redstone_block" => 10, "gold_ore" => 20, "iron_ore" => 10, "diamond_ore" => 5, "redstone_ore" => 10, "obsidian" => 5, "slime_block" => 5, "blue_stained_clay" => 5]),
			"m" => new Mine("m", 12, new Vector3(494.5, 42, 389.5), new Vector3(490, 41, 397), new Vector3(474, 25, 381), $def, ["iron_block" => 20, "gold_block" => 20, "redstone_block" => 10, "gold_ore" => 15, "iron_ore" => 10, "diamond_ore" => 10, "redstone_ore" => 10, "diorite" => 5]),
			"n" => new Mine("n", 12, new Vector3(497.5, 42, 461.5), new Vector3(492, 41, 469), new Vector3(476, 25, 453), $def, ["iron_block" => 20, "gold_block" => 25, "redstone_block" => 10, "gold_ore" => 10, "iron_ore" => 10, "diamond_ore" => 10, "redstone_ore" => 10, "blue_stained_clay" => 5]),
			"o" => new Mine("o", 12, new Vector3(497.5, 41, 529.5), new Vector3(492, 40, 537), new Vector3(476, 24, 521), $def, ["iron_block" => 10, "gold_block" => 25, "redstone_block" => 10, "gold_ore" => 10, "iron_ore" => 10, "diamond_ore" => 15, "redstone_ore" => 15, "orange_stained_clay" => 5]),

			"p" => new Mine("p", 18, new Vector3(577.5, 37, 234.5), new Vector3(571, 35, 242), new Vector3(555, 19, 226), $def, ["iron_block" => 15, "gold_block" => 30, "redstone_block" => 10, "iron_ore" => 10, "diamond_ore" => 15, "redstone_ore" => 15, "end_stone" => 5]),
			"q" => new Mine("q", 18, new Vector3(570.5, 41, 305.5), new Vector3(564, 40, 313), new Vector3(548, 23, 297), $def, ["iron_block" => 10, "gold_block" => 35, "redstone_block" => 20, "iron_ore" => 10, "diamond_ore" => 15, "lapis_ore" => 10]),
			"r" => new Mine("r", 18, new Vector3(576.5, 39, 376.5), new Vector3(570, 38, 384), new Vector3(554, 22, 368), $def, ["iron_block" => 10, "gold_block" => 35, "redstone_block" => 20, "diamond_ore" => 20, "redstone_block" => 15, "end_stone" => 5]),
			"s" => new Mine("s", 18, new Vector3(588.5, 39, 449.5), new Vector3(582, 38, 457), new Vector3(566, 22, 441), $def, ["iron_block" => 10, "gold_block" => 40, "redstone_block" => 20, "diamond_ore" => 10, "diamond_block" => 10, "lapis_block" => 15]),
			"t" => new Mine("t", 18, new Vector3(583.5, 38, 522.5), new Vector3(577, 37, 530), new Vector3(561, 21, 514), $def, ["iron_block" => 15, "gold_block" => 40, "redstone_block" => 10, "diamond_ore" => 5, "diamond_block" => 20, "lapis_block" => 10]),

			"u" => new Mine("u", 22, new Vector3(645.5, 41, 230.5), new Vector3(639, 40, 238), new Vector3(623, 24, 222), $def, ["iron_block" => 10, "gold_block" => 45, "redstone_block" => 10, "diamond_block" => 20, "lapis_block" => 10, "end_stone" => 5]),
			"v" => new Mine("v", 22, new Vector3(652.5, 43, 311.5), new Vector3(646, 42, 319), new Vector3(630, 12, 303), $def, ["iron_block" => 10, "gold_block" => 45, "redstone_block" => 5, "diamond_block" => 25, "lapis_block" => 10, "andesite" => 5]),
			"w" => new Mine("w", 22, new Vector3(651.5, 42, 389.5), new Vector3(645, 41, 397), new Vector3(629, 25, 381), $def, ["iron_block" => 10, "gold_block" => 45, "redstone_block" => 5, "diamond_block" => 25, "lapis_block" => 15]),
			"x" => new Mine("x", 22, new Vector3(655.5, 42, 464.5), new Vector3(649, 41, 472), new Vector3(633, 25, 456), $def, ["iron_block" => 10, "gold_block" => 40, "diamond_block" => 25, "lapis_block" => 15, "emerald_ore" => 10]),
			"y" => new Mine("y", 22, new Vector3(655.5, 44, 543.5), new Vector3(650, 43, 551), new Vector3(634, 27, 535), $def, ["gold_block" => 30, "diamond_block" => 30, "lapis_block" => 10, "emerald_ore" => 10, "emerald_block" => 10, "nether_brick_block" => 10]),

			"z" => new Mine("z", 25, new Vector3(27.5, 50, 583.5), new Vector3(19, 48, 577), new Vector3(35, 32, 561), $def, ["iron_block" => 10, "gold_block" => 30, "diamond_block" => 30, "lapis_block" => 10, "emerald_ore" => 5, "emerald_block" => 15]),
		] as $name => $mine) $this->mines[$name] = $mine;

		foreach ([
			"p1" => new PrestigeMine("p1", 25, new Vector3(47.5, 52, -91.5), new Vector3(33, 51, -113), new Vector3(49, 30, -129), Server::getInstance()->getWorldManager()->getWorldByName("Mines"), ["iron_block" => 5, "gold_block" => 30, "diamond_block" => 35, "lapis_block" => 5, "purpur_block" => 10, "emerald_block" => 15]),
			"p5" => new PrestigeMine("p5", 25, new Vector3(361.5, 53, 35.5), new Vector3(392, 51, 36), new Vector3(408, 32, 52), Server::getInstance()->getWorldManager()->getWorldByName("Mines"), ["gold_block" => 30, "diamond_block" => 45, "purpur_block" => 10, "emerald_block" => 15], 5),
			"p10" => new PrestigeMine("p10", 25, new Vector3(44.5, 53, 431.5), new Vector3(63, 51, 460), new Vector3(47, 31, 476), Server::getInstance()->getWorldManager()->getWorldByName("Mines"), ["gold_block" => 25, "diamond_block" => 45, "purpur_block" => 10, "emerald_block" => 20], 10),
			"p15" => new PrestigeMine("p15", 25, new Vector3(220.5, 52, 497.5), new Vector3(213, 51, 467), new Vector3(229, 32, 451), Server::getInstance()->getWorldManager()->getWorldByName("Mines"), ["gold_block" => 28, "diamond_block" => 39, "glowing_obsidian_ore" => 3, "purpur_block" => 10, "emerald_block" => 20], 15),
			"p20" => new PrestigeMine("p20", 25, new Vector3(15.5, 52, -274.5), new Vector3(30, 50, -289), new Vector3(46, 31, -273), Server::getInstance()->getWorldManager()->getWorldByName("Mines"), ["gold_block" => 23, "diamond_block" => 42, "purpur_block" => 5, "emerald_block" => 25, "glowing_obsidian_ore" => 5], 20),
			"p25" => new PrestigeMine("p25", 25, new Vector3(14.5, 52, -464.5), new Vector3(34, 50, -472), new Vector3(50, 31, -456), Server::getInstance()->getWorldManager()->getWorldByName("Mines"), ["gold_block" => 11, "diamond_block" => 35, "purpur_block" => 5, "emerald_block" => 30, "quartz_block" => 10, "glowing_obsidian_ore" => 9], 25),
		] as $name => $mine) $this->mines[$name] = $mine;
		$this->mines["vip"] = new Mine("vip", 25, new Vector3(121.5, 45, -197.5), new Vector3(129, 42, -192), new Vector3(113, 26, -176), $def, ["gold_block" => 5, "lapis_block" => 5, "concrete:X" => 20, "sea_lantern" => 15, "red_nether_brick" => 10, "sponge" => 5, "iron_ore" => 20, "magma" => 10, "prismarine_bricks" => 10]);

		//Server::getInstance()->getWorldManager()->loadWorld("pvp-mine", true);
		//$level = Server::getInstance()->getWorldManager()->getWorldByName("pvp-mine");
		//$this->mines["pvp"] = new PvPMine("pvp", 5, [new Vector3(37.5, 44, 64.5), new Vector3(13.5, 44, 15.5), new Vector3(69.5, 44, -8.5), new Vector3(71.5, 44, 55.5), new Vector3(33.5, 44, 52.5)], new Vector3(58,19,6), new Vector3(36,42,28), $level, ["quartz_block" => 30, "diamond_block" => 5, "emerald_block" => 5, "lapis_block" => 5, "nether_brick_block" => 35, "redstone_block" => 5, "prismarine" => 15]);

		//Server::getInstance()->getWorldManager()->loadWorld("pvpmine", true);
		//$level = Server::getInstance()->getWorldManager()->getWorldByName("pvpmine");
		//$this->mines["pvp"] = new PvPMine("pvp", 5, [new Vector3(153.5,18,85.5), new Vector3(175.5, 18, 77.5), new Vector3(177.5, 18, 121.5), new Vector3(130.5, 18, 121.5), new Vector3(126.5, 18, 103.5), new Vector3(152.5, 18, 73.5)], new Vector3(158,17,105), new Vector3(148,12,95), Server::getInstance()->getWorldManager()->getWorldByName("pvpmine"), ["quartz_block" => 30, "diamond_block" => 5, "emerald_block" => 5, "lapis_block" => 5, "nether_brick_block" => 35, "redstone_block" => 5, "prismarine" => 15]);

		$this->mines["pvp"] = new PvPMine(
			"pvp",
			5,
			[
				new Vector3(-109.5, 50, 748.5),
				new Vector3(-114.5, 50, 726.5),
				new Vector3(-100, 50, 704),
				new Vector3(-84, 50, 695),
				new Vector3(-60, 50, 690),
				new Vector3(-38, 50, 699),
				new Vector3(-17, 50, 705),
				new Vector3(-20, 50, 725),
				new Vector3(-28, 50, 750),
				new Vector3(-48, 50, 762),
				new Vector3(-73, 50, 763),
				new Vector3(-100, 50, 753),
				new Vector3(-118, 50, 737)
			],
			new Vector3(-72, 40, 721),
			new Vector3(-60, 49, 733),
			$isPvP ? Server::getInstance()->getWorldManager()->getWorldByName("s1pvpremastered") : $def,
			[
				"quartz_block" => 30,
				"diamond_block" => 5,
				"emerald_block" => 5,
				"lapis_block" => 5,
				"nether_brick_block" => 35,
				"redstone_block" => 5,
				"prismarine" => 15
			]
		);
	}

	public function getMines(): array {
		return $this->mines;
	}

	public function getMineByName(string $name): ?Mine {
		return $this->mines[$name] ?? null;
	}

	public function mineExists(string $name): bool {
		return isset($this->mines[$name]);
	}
}
