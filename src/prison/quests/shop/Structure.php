<?php namespace prison\quests\shop;

interface Structure{

	const CATEGORY_KEYS = 0;
	const CATEGORY_ITEMS = 1;
	const CATEGORY_OTHER = 2;

	const CATEGORY_NAMES = [
		self::CATEGORY_KEYS => "Mystery Box Keys",
		self::CATEGORY_ITEMS => "Rare Items",
		self::CATEGORY_OTHER => "Other Goodies",
	];

	const CATEGORY_PRIZES = [
		self::CATEGORY_KEYS => [
			"key:iron:1" => 3,
			"key:iron:5" => 5,
			"key:iron:10" => 10,
			"key:iron:25" => 15,
			"key:iron:50" => 25,

			"key:gold:1" => 5,
			"key:gold:5" => 10,
			"key:gold:10" => 25,
			"key:gold:25" => 35,

			"key:diamond:1" => 10,
			"key:diamond:5" => 20,
			"key:diamond:10" => 30,

			"key:emerald:1" => 15,
			"key:emerald:5" => 30, // Shane I added a few more prizes and changed the prices so they seem more worth it! 
		],

		self::CATEGORY_ITEMS => [
			"item:creeper_head:1" => 5,
			"item:dragon_head:1" => 10,
			"item:player_head:1" => 15,
			"item:piglin_head:1" => 20,
			"item:skeleton_skull:1" => 25,
			"item:wither_skeleton_skull:1" => 30,
			"item:zombie_head:1" => 40,

			"item:golden_apple:8" => 5,
			"item:golden_apple:16" => 10,
			"item:golden_apple:32" => 15,

			"item:enchanted_golden_apple:4" => 10,
			"item:enchanted_golden_apple:16" => 25,
			"item:enchanted_golden_apple:32" => 40,

			"item:obsidian:16" => 10,
			"item:obsidian:64" => 30,

			"item:pouch_of_essence:1:250" => 10,
			"item:pouch_of_essence:4:250" => 30,
		],
	];

}