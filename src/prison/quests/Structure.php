<?php namespace prison\quests;

use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class Structure{

	const LEVEL_EASY = 1;
	const LEVEL_NORMAL = 2;
	const LEVEL_HARD = 3;

	const GARY_GRASSHOPPER = 0;
	const BILLY = 1;
	const SAMMY_SHEEP = 2;
	const QUAZARK = 3;
	const BLADE = 4;
	const FLYING_DUTCHMAN = 5;
	const JACK_LUMBERJACK = 6;
	const BOBBY_NOOB = 7;
	const SANDY = 8;
	const DJ_MINOR = 9;
	const JAMES_FARMER = 10;

	public static array $QUEST_TAKES = [];

	public static function setup(): void {
		self::$QUEST_TAKES = [
			self::SAMMY_SHEEP => [
				VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::BLACK)->asItem(),
				VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::WHITE)->asItem(),
				VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::ORANGE)->asItem(),
				VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::MAGENTA)->asItem(),
				VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::LIGHT_BLUE)->asItem(),
				VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::YELLOW)->asItem(),
				VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::LIME)->asItem(),
				VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::PINK)->asItem(),
				VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::GRAY)->asItem(),
				VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::LIGHT_GRAY)->asItem(),
				VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::CYAN)->asItem(),
				VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::PURPLE)->asItem(),
				VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::BLUE)->asItem(),
				VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::BROWN)->asItem(),
				VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::GREEN)->asItem(),
				VanillaBlocks::STAINED_CLAY()->setColor(DyeColor::RED)->asItem()
			],
			self::QUAZARK => [
				VanillaBlocks::COBBLESTONE()->asItem()->setCount(64),
				VanillaBlocks::COBBLESTONE()->asItem()->setCount(64),
				VanillaBlocks::COBBLESTONE()->asItem()->setCount(64),
				VanillaBlocks::COBBLESTONE()->asItem()->setCount(64),
				VanillaBlocks::OAK_PLANKS()->asItem()->setCount(64),
				VanillaBlocks::OAK_PLANKS()->asItem()->setCount(64),
				VanillaBlocks::OAK_PLANKS()->asItem()->setCount(64),
				VanillaBlocks::OAK_PLANKS()->asItem()->setCount(64)
			],
			self::BLADE => [
				VanillaItems::IRON_SWORD(),
				VanillaItems::IRON_SWORD(),
				VanillaItems::IRON_SWORD(),
				VanillaItems::IRON_SWORD(),
				VanillaItems::IRON_SWORD(),
				VanillaItems::IRON_SWORD(),
				VanillaItems::IRON_SWORD(),
				VanillaItems::IRON_SWORD(),
				VanillaItems::IRON_SWORD(),
				VanillaItems::IRON_SWORD()
			],
			self::FLYING_DUTCHMAN => [ // "i:5:0:64"
				VanillaBlocks::OAK_PLANKS()->asItem()->setCount(64)
			]
		];
	}

	const QUEST_DATA = [
		self::GARY_GRASSHOPPER => [
			"level" => self::LEVEL_EASY,
			"name" => "Gary the Grasshopper",
			"rank" => "a",
			"messages" => [
				"requirement" => "Jump 250 times",

				"request" => "Hey there buddy! I've been looking EVERYWHERE for a little prisoner that can jump... Thought it'd be funny.",
				"incomplete" => "Why are you back? You obviously haven't jumped 250 times...",
				"done" => "I'm impressed... Come back to me to claim your reward!",
				"undone" => "How the fuck did you reverse jump to uncomplete this quest?",
				"complete" => "Holy! You actually jumped 250 times? Hilarious! Okay here are your prizes.",
			],
			"startingprogress" => ["jumps" => [0,250]],
		],
		self::BILLY => [
			"level" => self::LEVEL_NORMAL,
			"name" => "Billy",
			"rank" => "d",
			"messages" => [
				"requirement" => "Kill 5 people in PvP Mode",

				"request" => "Hey! Would you do a favor for me kid? I need 5 people dead. If you can do that, I'll give you a reward.",
				"incomplete" => "I want 5 people dead! Don't come back until your sword has the blood of your enemies dripping off of it!",
				"done" => "Dang, didn't think you'd make the cut! Get it? Not saying I'm proud, but good work squire.",
				"undone" => "Meh, didn't even believe in you, get lost kid!",
				"complete" => "Wait what?! Seriously who taught you how to be a boss!",
			],
			"startingprogress" => ["kills" => [0,5]],
		],
		self::SAMMY_SHEEP => [
			"level" => self::LEVEL_EASY,
			"name" => "Sammy the Sheep",
			"rank" => "g",
			"messages" => [
				"requirement" => "Obtain 1 of each Hardened Clay from the black market and hand it in to Sammy.",

				"request" => "Hiya! I'm Sammy, recently I wanted to create a rainbow sheep, but I need some colorful clay in order to do that! Would you please bring me 1 of each colored Hardened clay from the Black Market, so I can make the most BEAUTIFUL sheep?",
				"incomplete" => "Uh... I thought you were going to bring me colored Hardened Clay? Don't come back until you get all the colors.",
				"done" => "Good work! That rainbow sheep will become a reality!",
				"undone" => "I thought I could trust you.. **sob** **sob**",
				"complete" => "GRACIAS!! I love you, here's your reward.",
			],
			"startingprogress" => ["clay colors" => [0,16]],
		],
		self::QUAZARK => [
			"level" => self::LEVEL_EASY,
			"name" => "Quazark",
			"rank" => "c",
			"messages" => [
				"requirement" => "Bring Quazark 256 Cobblestone and 256 Oak Planks.",

				"request" => "Hey, can you help me get materials for my house? I'm not really the 'resource collecting' type, but you're a prisoner right? Go collect some resources for me!",
				"incomplete" => "I'm trying to build a house! Please don't come back until you have what I need..",
				"done" => "Yay! You have all the resources I need, now come back so I can collect them!",
				"undone" => "...where did the resources go? I thought you were going to help me build a house?",
				"complete" => "Yay, thanks for all of your help! Now I can go build my brand new house!",
			],
			"startingprogress" => ["cobblestone" => [0,256],"oak planks" => [0,256]],
		],
		self::BLADE => [
			"level" => self::LEVEL_EASY,
			"name" => "Blade",
			"rank" => "b",
			"messages" => [
				"requirement" => "Return to Blade with 10 Iron Swords.",

				"request" => "I've had enough! I am not going to stay in this prison any longer... Please bring me 10 Iron Swords. I am going to be busting out of here, and I don't care how long it takes!",
				"incomplete" => "I need those swords! Don't come back until you get those 10 Iron Swords for me!",
				"done" => "You have the items I desire... Please return to me to hand them over.",
				"undone" => "Wow.. I actually thought that you were willing to help me out.. you're disappointing.",
				"complete" => "Finally! I'm beginning to lose my mind in here... I'll be out of here soon, if all works well...",
			],
			"startingprogress" => ["iron swords" => [0,10]],
		],
		self::FLYING_DUTCHMAN => [
			"level" => self::LEVEL_EASY,
			"name" => "Flying Dutchman",
			"rank" => "a",
			"messages" => [
				"requirement" => "Bring the Flying Dutchman 64 oak planks.",

				"request" => "Hey mah boi, bring me 64 oak planks and I'll give you some goodies.",
				"incompete" => "This ain't what I asked for... What did I hire you for if you can't do the job?!",
				"done" => "You have the goods, now bring them back... Or I will remove you from my realm, you Plebian!",
				"undone" => "What are you doing standing around... Get to work! I'm not finishing this ship alone.",
				"complete" => "Thank you my good man, you didn't disappoint me after all, like all the others! Now, get away.. Or you'll walk the plank!",
			],
			"startingprogress" => ["oak planks" => [0,64]],
		],
	];

	public static function getPointsFromLevel($level = self::LEVEL_EASY){
		return [
			self::LEVEL_EASY => 5,
			self::LEVEL_NORMAL => 10,
			self::LEVEL_HARD => 15
		][$level];
	}

	

}