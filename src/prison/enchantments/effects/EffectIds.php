<?php namespace prison\enchantments\effects;

use pocketmine\utils\TextFormat;

class EffectIds{

	const TYPE_SWORD = 0;
	const TYPE_TOOL = 1;
	//no bows!!

	const RARITY_COMMON = 1;
	const RARITY_UNCOMMON = 2;
	const RARITY_RARE = 3;
	const RARITY_LEGENDARY = 4;

	const APPLY_COSTS = [
		self::RARITY_COMMON => 10,
		self::RARITY_UNCOMMON => 20,
		self::RARITY_RARE => 25,
		self::RARITY_LEGENDARY => 35,
	];

	const RARITY_COLORS = [
		self::RARITY_COMMON => TextFormat::GREEN,
		self::RARITY_UNCOMMON => TextFormat::DARK_GREEN,
		self::RARITY_RARE => TextFormat::YELLOW,
		self::RARITY_LEGENDARY => TextFormat::GOLD,
	];

	const RARITY_TAGS = [
		self::RARITY_COMMON => "common",
		self::RARITY_UNCOMMON => "uncommon",
		self::RARITY_RARE => "rare",
		self::RARITY_LEGENDARY => "legendary",
	];

	const EFFECTS = [
		//Common
		self::SHOOK => [
			"name" => "Shook",
			"description" => "",

			"rarity" => self::RARITY_COMMON,
			"type" => self::TYPE_SWORD,
			"obtainable" => false
		],
		self::POOF => [
			"name" => "Poof",
			"description" => "Vaporizes target into clouds",

			"rarity" => self::RARITY_COMMON,
			"type" => self::TYPE_SWORD
		],
		self::COOKED => [
			"name" => "Cooked",
			"description" => "Cook your target like a steak on a grill",

			"rarity" => self::RARITY_COMMON,
			"type" => self::TYPE_SWORD
		],
		self::BLACKOUT => [
			"name" => "Blackout",
			"description" => "Turn your victim to dust!",

			"rarity" => self::RARITY_COMMON,
			"type" => self::TYPE_SWORD
		],
		self::FLURRY => [
			"name" => "Flurry",
			"description" => "Creates a snowball cannon at target's death point",

			"rarity" => self::RARITY_COMMON,
			"type" => self::TYPE_SWORD
		],
		self::SPLASH => [
			"name" => "Splash",
			"description" => "Splish sploosh",

			"rarity" => self::RARITY_COMMON,
			"type" => self::TYPE_SWORD
		],
		self::DREAM => [
			"name" => "Dream",
			"description" => "Almost made it..",

			"rarity" => self::RARITY_COMMON,
			"type" => self::TYPE_SWORD
		],


		//Uncommon
		self::LAVA_RAIN => [
			"name" => "Lava Rain",
			"description" => "Make it rain down on your target's death point, but lava",

			"rarity" => self::RARITY_UNCOMMON,
			"type" => self::TYPE_SWORD
		],
		self::TNT => [
			"name" => "TNT",
			"description" => "Morphs target into TNT and detonates them",

			"rarity" => self::RARITY_UNCOMMON,
			"type" => self::TYPE_SWORD
		],
		self::LITTLE_MERMAID => [
			"name" => "Little Mermaid",
			"description" => "Turn target into a fish!",

			"rarity" => self::RARITY_UNCOMMON,
			"type" => self::TYPE_SWORD
		],
		self::FLOOD => [
			"name" => "Flood",
			"description" => "Make it rain down on your target's death point",

			"rarity" => self::RARITY_UNCOMMON,
			"type" => self::TYPE_SWORD
		],
		self::THORS_WRATH => [
			"name" => "Thor's Wrath",
			"description" => "Multiple lightning strikes sent from the gods",

			"rarity" => self::RARITY_UNCOMMON,
			"type" => self::TYPE_SWORD
		],

		//Rare
		self::RIP => [
			"name" => "R.I.P.",
			"description" => "They will be missed",

			"rarity" => self::RARITY_RARE,
			"type" => self::TYPE_SWORD
		],
		self::AURA => [
			"name" => "Aura",
			"description" => "Summons an elemental sphere of water and lava",

			"rarity" => self::RARITY_RARE,
			"type" => self::TYPE_SWORD
		],
		self::BURN => [
			"name" => "Burn",
			"description" => "Summons a fiery ball of death",

			"rarity" => self::RARITY_RARE,
			"type" => self::TYPE_SWORD
		],
		self::COLD_AND_CREEPY => [
			"name" => "Cold and Creepy",
			"description" => "Summons snow golems that wander around",

			"rarity" => self::RARITY_RARE,
			"type" => self::TYPE_SWORD
		],
		self::TRADE_OFF => [
			"name" => "Trade Off",
			"description" => "Trade your enemy off to a villager for emeralds",

			"rarity" => self::RARITY_RARE,
			"type" => self::TYPE_SWORD
		],
		self::EXPLOSIVE_SURPRISE => [
			"name" => "Explosive Surprise",
			"description" => "Rainbow Llama!!!",

			"rarity" => self::RARITY_RARE,
			"type" => self::TYPE_SWORD
		],

		//Legendary
		self::WITCHCRAFT => [
			"name" => "Witchcraft",
			"description" => "Summons bats that circle death point of your target!",

			"rarity" => self::RARITY_LEGENDARY,
			"type" => self::TYPE_SWORD
		],
		self::CHICKEN => [
			"name" => "Chicken",
			"description" => "Turns target into crazy egg laying chicken",

			"rarity" => self::RARITY_LEGENDARY,
			"type" => self::TYPE_SWORD
		],
		self::APOCALYPSE => [
			"name" => "Apocalypse",
			"description" => "",

			"rarity" => self::RARITY_LEGENDARY,
			"type" => self::TYPE_SWORD,
			"obtainable" => false
		],
		self::CREEPY_CRAWLY => [
			"name" => "Creepy Crawly",
			"description" => "Spawns a bunch of CREEPY cave spiders and silverfish",

			"rarity" => self::RARITY_LEGENDARY,
			"type" => self::TYPE_SWORD
		],
		self::DEAD_RIDER => [
			"name" => "Dead Rider",
			"description" => "",

			"rarity" => self::RARITY_LEGENDARY,
			"type" => self::TYPE_SWORD,
			"obtainable" => false
		],
		self::ENDERS_WRATH => [
			"name" => "Ender's Wrath",
			"description" => "",

			"rarity" => self::RARITY_LEGENDARY,
			"type" => self::TYPE_SWORD,
			"obtainable" => false
		],
		self::L => [
			"name" => "L",
			"description" => "L",

			"rarity" => self::RARITY_LEGENDARY,
			"type" => self::TYPE_SWORD
		],
		self::GG => [
			"name" => "GG",
			"description" => "gg, bro",

			"rarity" => self::RARITY_LEGENDARY,
			"type" => self::TYPE_SWORD,
			"obtainable" => false
		],

	];

	//Sword

	//Common
	const SHOOK = 1;
	const POOF = 2;
	const COOKED = 3;
	const BLACKOUT = 4;
	//const POPPED = 5;
	const FLURRY = 6;
	const SPLASH = 7;
	//const OOF = 8;
	const DREAM = 9;

	//Uncommon
	const LAVA_RAIN = 12;
	const TNT = 13;
	const LITTLE_MERMAID = 14;
	const FLOOD = 15;
	const THORS_WRATH = 16;
	const FIREWORKS = 17;

	//Rare
	const RIP = 21;
	const AURA = 22;
	const BURN = 23;
	const COLD_AND_CREEPY = 24;
	const TRADE_OFF = 25;
	const EXPLOSIVE_SURPRISE = 26;

	//Legendary
	const WITCHCRAFT = 31;
	const CHICKEN = 32;
	const APOCALYPSE = 33;
	const DEAD_RIDER = 34;
	const CREEPY_CRAWLY = 35;
	const ENDERS_WRATH = 36;
	const L = 37;
	const GG = 38;

	//Tool (mining type)
}