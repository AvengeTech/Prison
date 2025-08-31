<?php

namespace prison\shops;

use core\utils\conversion\LegacyItemIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class Structure {

	const CATEGORY_MINERALS = 0;
	const CATEGORY_MINERAL_BLOCKS = 1;
	const CATEGORY_BLOCKS = 2;
	const CATEGORY_WOOD = 3;
	const CATEGORY_FARMING = 4;
	const CATEGORY_CLAY = 5;
	const CATEGORY_MATERIALS = 6;
	const CATEGORY_TOOLS = 7;
	const CATEGORY_ARMOR = 8;
	const CATEGORY_FOOD = 9;
	const CATEGORY_DYE = 10;
	const CATEGORY_STAINED_GLASS = 11;
	const CATEGORY_CONCRETE = 12;

	const CATEGORY_NAME = [
		self::CATEGORY_MINERALS => "Minerals",
		self::CATEGORY_MINERAL_BLOCKS => "Mineral Blocks",
		self::CATEGORY_BLOCKS => "Blocks",
		self::CATEGORY_WOOD => "Wood",
		self::CATEGORY_FARMING => "Farming",
		self::CATEGORY_CLAY => "Clay",
		self::CATEGORY_MATERIALS => "Materials",
		self::CATEGORY_TOOLS => "Tools",
		self::CATEGORY_ARMOR => "Armor",
		self::CATEGORY_FOOD => "Food",
		self::CATEGORY_DYE => "Dyes",
		self::CATEGORY_STAINED_GLASS => "Stained Glass",
		self::CATEGORY_CONCRETE => "Concrete",
	];

	const CATEGORY_DESCRIPTION = [
		self::CATEGORY_MINERALS => "Minerals... The most valuable ones!",
		self::CATEGORY_MINERAL_BLOCKS => "Yeah, we've got the rarest mineral blocks aswell.",
		self::CATEGORY_BLOCKS => "Other blocks that aren't categorized can be found here.",
		self::CATEGORY_WOOD => "All your wood stuff can be found here.",
		self::CATEGORY_FARMING => "All your farming necessities, right here.",
		self::CATEGORY_CLAY => "Clay, yeah... We got it!",
		self::CATEGORY_MATERIALS => "Crafting materials and other things are found here.",
		self::CATEGORY_TOOLS => "Tools to help you in the mines, or during riots, can be found here.",
		self::CATEGORY_ARMOR => "We've got armor, you'll need it for those bloody riots...",
		self::CATEGORY_FOOD => "Here you'll find the best food in a prison.",
		self::CATEGORY_DYE => "Dye your leather armor, wool, or other things.",
		self::CATEGORY_STAINED_GLASS => "Make your plots prettier.",
		self::CATEGORY_CONCRETE => "Make your plots prettier.",
	];

	const CATEGORY_IMAGE = [
		self::CATEGORY_MINERALS => "[REDACTED]",
		self::CATEGORY_MINERAL_BLOCKS => "[REDACTED]",
		self::CATEGORY_BLOCKS => "[REDACTED]",
		self::CATEGORY_WOOD => "[REDACTED]",
		self::CATEGORY_FARMING => "[REDACTED]",
		self::CATEGORY_CLAY => "[REDACTED]",
		self::CATEGORY_MATERIALS => "[REDACTED]",
		self::CATEGORY_TOOLS => "[REDACTED]",
		self::CATEGORY_ARMOR => "[REDACTED]",
		self::CATEGORY_FOOD => "[REDACTED]",
		self::CATEGORY_DYE => "[REDACTED]",
		self::CATEGORY_STAINED_GLASS => "[REDACTED]",
		self::CATEGORY_CONCRETE => "[REDACTED]",
	];

	//Format [sell,buy,can sell(true),can buy(true),max stack(64)]
	const PRICES = [
		self::CATEGORY_MINERALS => [
			"coal" => [2, 6, true, false], //Coal
			"iron_ingot" => [5, 10, true, false], //Iron Ingot
			"gold_ingot" => [7, 14, true, false], //Gold Ingot
			"lapis_lazuli" => [2, 4, true, false], //Lapis Lazuli
			"redstone_dust" => [4, 8, true, false], //Redstone Dust
			"diamond" => [10, 20, true, false], //Diamond
			"emerald" => [15, 30, true, false], //Emerald
		],
		self::CATEGORY_MINERAL_BLOCKS => [
			"coal_block" => [18, 54, true, false], //Coal Block
			"iron_block" => [45, 90, true, false], //Iron Block
			"gold_block" => [63, 126, true, false], //Gold Block
			"lapis_lazuli_block" => [18, 36, true, false], //Lapis Lazuli Block
			"redstone_block" => [36, 72, true, false], //Redstone Block
			"diamond_block" => [90, 180, true, false], //Diamond Block
			"emerald_block" => [135, 270, true, false], //Emerald Block
		],
		self::CATEGORY_BLOCKS => [
			"stone" => [2, 4], //Stone
			"granite" => [2, 4], //Granite
			"polished_granite" => [2, 4], //Polished Granite
			"diorite" => [2, 4], //Diorite
			"polished_diorite" => [2, 4], //Polished Diorite
			"andesite" => [2, 4], //Andesite
			"polished_andesite" => [2, 4], //Polished Andesite
			"cobblestone" => [1, 2], //Cobblestone


			"grass" => [1, 2], //Grass
			"dirt" => [1, 2], //Dirt

			"sand" => [2, 4], //Sand
			"sandstone" => [4, 8], //Sandstone
			"red_sand" => [2, 4], //Red Sand
			"red_sandstone" => [4, 8], //Red Sandstone

			"gravel" => [2, 4], //Gravel
			"sponge" => [2, 4, true, false], //SpongeBob SquarePants
			"end_stone" => [10, 20], //End Stone
			"slime_block" => [10, 20], //Slime Block
			"mossy_cobblestone" => [1, 2], //Moss Stone
			"obsidian" => [10, 20], //Obsidian
			"netherrack" => [4, 8], //Netherrack
			"ice" => [5, 10], //Ice
			"snow" => [5, 10], //Snow Block
			"glowstone" => [8, 16], //Glowstone
			"stone_bricks" => [2, 4], //Stone Bricks
			"purpur_block" => [25, 50], //Purpur
			"nether_wart_block" => [52, 104], //Purpur
			"glowing_obsidian" => [185, 370], //Glowing Obsidian

			"quartz_block" => [18, 36, true, false], //Quartz
			"nether_bricks" => [4, 8], //Nether Brick Block
			"red_nether_bricks" => [5, 10], //Red Nether Brick Block
			"prismarine" => [4, 8], //Prismarine
			"dark_prismarine" => [4, 8], //Dark Prismarine
			"magma" => [10, 20], //Magma Block
			"sea_lantern" => [2, 4], //Sea Lantern
		],
		self::CATEGORY_WOOD => [
			"oak_log" => [1, 2], //Oak Log
			"spruce_log" => [1, 2], //Spruce Log
			"birch_log" => [1, 2], //Birch Log
			"jungle_log" => [1, 2], //Jungle Log
			"acacia_log" => [1, 2], //Acacia Log
			"dark_oak_log" => [1, 2], //Dark Oak Log

			"oak_planks" => [1, 2, false, true], //Oak Plank
			"spruce_planks" => [1, 2, false, true], //Spruce Plank
			"birch_planks" => [1, 2, false, true], //Birch Plank
			"jungle_planks" => [1, 2, false, true], //Jungle Plank
			"acacia_planks" => [1, 2, false, true], //Acacia Plank
			"dark_oak_planks" => [1, 2, false, true], //Dark Oak Plank
		],
		self::CATEGORY_FARMING => [
			"wheat_seeds" => [2, 4], //Wheat Seeds
			"wheat" => [2, 4], //Wheat
			"hay_bale" => [18, 36], //Hay Bale
			"pumpkin" => [10, 20], //Pumpkin
			"lit_pumpkin" => [10, 40], //Jack o'Lantern
			"pumpkin_seeds" => [2, 4], //Pumpkin Seeds

			"vines" => [8, 0, true, false], //Vines

			"cactus" => [4, 8], //Cactus

			//"434:0" => [2,4], //Beetroot
			//"435:0" => [2,4], //Beetroot Seeds

			"melon_seeds" => [1, 2], //Melon Seeds
			"melon" => [1, 2], //Melon Slice
			"melon_block" => [9, 18], //Melon Block

			"oak_leaves" => [10, 20], //Oak Leaves
			"spruce_leaves" => [10, 20], //Spruce Leaves
			"birch_leaves" => [10, 20], //Birch Leaves
			"jungle_leaves" => [10, 20], //Jungle Leaves
			"acacia_leaves" => [10, 20], //Acacia Leaves
			"dark_oak_leaves" => [10, 20], //Dark Oak Leaves

			"oak_sapling" => [5, 10], //Oak Sapling
			"spruce_sapling" => [5, 10], //Spruce Sapling
			"birch_sapling" => [5, 10], //Birch Sapling
			"jungle_sapling" => [5, 10], //Jungle Sapling
			"acacia_sapling" => [5, 10], //Acacia Sapling
			"dark_oak_sapling" => [5, 10], //Dark Oak Sapling

			"bone_meal" => [0, 15, false, true, 64, "Bonemeal"], //Bonemeal
		],
		self::CATEGORY_CLAY => [
			"clay" => [1, 2], //Clay
			"clay_block" => [4, 8], //Clay Block

			"hardened_clay" => [5, 10], //Hardened Clay
			"white_stained_clay" => [5, 10], //White Hardened Clay
			"orange_stained_clay" => [5, 10], //Orange Hardened Clay
			"magenta_stained_clay" => [5, 10], //Magenta Hardened Clay
			"light_blue_stained_clay" => [5, 10], //Light Blue Hardened Clay
			"yellow_stained_clay" => [5, 10], //Yellow Hardened Clay
			"lime_stained_clay" => [5, 10], //Lime Hardened Clay
			"pink_stained_clay" => [5, 10], //Pink Hardened Clay
			"gray_stained_clay" => [5, 10], //Gray Hardened Clay
			"light_gray_stained_clay" => [5, 10], //Light Gray Hardened Clay
			"cyan_stained_clay" => [5, 10], //Cyan Hardened Clay
			"purple_stained_clay" => [5, 10], //Purple Hardened Clay
			"blue_stained_clay" => [5, 10], //Blue Hardened Clay
			"brown_stained_clay" => [5, 10], //Brown Hardened Clay
			"green_stained_clay" => [5, 10], //Green Hardened Clay
			"red_stained_clay" => [5, 10], //Red Hardened Clay
			"black_stained_clay" => [5, 10], //Black Hardened Clay
		],
		self::CATEGORY_MATERIALS => [
			"glowstone_dust" => [2, 4], //Glowstone
			"leather" => [3, 0, true, false], //Leather
			"feather" => [3, 0, true, false], //Feather
		],
		self::CATEGORY_TOOLS => [
			//Stone stuff
			"stone_shovel" => [0, 0, false, true, 1],
			"stone_pickaxe" => [0, 0, false, true, 1],
			"stone_axe" => [0, 0, false, true, 1],

			//"267:0" => [0,125,false,true,1], //Iron Sword
			//"256:0" => [0,125,false,true,1], //Iron Shovel
			//"257:0" => [0,125,false,true,1], //Iron Pickaxe
			//"258:0" => [0,125,false,true,1], //Iron Axe

			"golden_sword" => [0, 100, false, true, 1], //Gold Sword
			"golden_shovel" => [0, 100, false, true, 1], //Gold Shovel
			"golden_pickaxe" => [0, 100, false, true, 1], //Gold Pickaxe
			"golden_axe" => [0, 100, false, true, 1], //Gold Axe

			//"276:0" => [0,175,false,true,1], //Diamond Sword
			//"277:0" => [0,175,false,true,1], //Diamond Shovel
			//"278:0" => [0,175,false,true,1], //Diamond Pickaxe
			//"279:0" => [0,175,false,true,1], //Diamond Axe

			"arrow" => [1, 2, true, false], //Arrow

			"crafting_table" => [0, 1, false], //Crafting Table
			"furnace" => [0, 1, false], //Furnace
			"chest" => [0, 5, false], //Chests
		],
		self::CATEGORY_ARMOR => [
			//"306:0" => [0,200,false,true,1], //Iron Helmet
			//"307:0" => [0,200,false,true,1], //Iron Chestplate
			//"308:0" => [0,200,false,true,1], //Iron Leggings
			//"309:0" => [0,200,false,true,1], //Iron Boots

			"golden_helmet" => [0, 150, false, true, 1], //Gold Helmet
			"golden_chestplate" => [0, 150, false, true, 1], //Gold Chestplate
			"golden_leggings" => [0, 150, false, true, 1], //Gold Leggings
			"golden_boots" => [0, 150, false, true, 1], //Gold Boots

			//"310:0" => [0,300,false,true,1], //Diamond Helmet
			//"311:0" => [0,300,false,true,1], //Diamond Chestplate
			//"312:0" => [0,300,false,true,1], //Diamond Leggings
			//"313:0" => [0,300,false,true,1], //Diamond Boots
		],
		self::CATEGORY_FOOD => [
			"raw_porkchop" => [1, 2], //Raw porkchop
			"cooked_porkchop" => [2, 4, false], //Cooked porkchop

			"raw_beef" => [1, 2], //Raw beef
			"steak" => [2, 4, false], //Steak

			"raw_chicken" => [1, 2], //Raw Chicken
			"cooked_chicken" => [2, 4, false], //Cooked Chicken

			"brown_mushroom" => [4, 8], //Brown mushroom
			"red_mushroom" => [4, 8], //Red mushroom
		],
		self::CATEGORY_DYE => [
			"white_dye" => [10, 0, true, false, 64, "White Dye"],
			"orange_dye" => [10, 0, true, false, 64, "Orange Dye"],
			"magenta_dye" => [10, 0, true, false, 64, "Magenta Dye"],
			"light_blue_dye" => [10, 0, true, false, 64, "Light Blue Dye"],
			"yellow_dye" => [10, 0, true, false, 64, "Dandelion Yellow"],
			"lime_dye" => [10, 0, true, false, 64, "Lime Dye"],
			"pink_dye" => [10, 0, true, false, 64, "Pink Dye"],
			"gray_dye" => [10, 0, true, false, 64, "Gray Dye"],
			"light_gray_dye" => [10, 0, true, false, 64, "Light Gray Dye"],
			"cyan_dye" => [10, 0, true, false, 64, "Cyan Dye"],
			"purple_dye" => [10, 0, true, false, 64, "Purple Dye"],
			"blue_dye" => [10, 0, true, false, 64, "Blue Dye"],
			"cocoa_beans" => [10, 0, true, false, 64, "Coco Beans"],
			"green_dye" => [10, 0, true, false, 64, "Cactus Green"],
			"red_dye" => [10, 0, true, false, 64, "Rose Red"],
			"ink_sac" => [10, 0, true, false, 64, "Ink Sac"]
		],
		self::CATEGORY_STAINED_GLASS => [
			"white_stained_glass" => [5, 10],
			"orange_stained_glass" => [5, 10],
			"magenta_stained_glass" => [5, 10],
			"light_blue_stained_glass" => [5, 10],
			"yellow_stained_glass" => [5, 10],
			"lime_stained_glass" => [5, 10],
			"pink_stained_glass" => [5, 10],
			"gray_stained_glass" => [5, 10],
			"light_gray_stained_glass" => [5, 10],
			"cyan_stained_glass" => [5, 10],
			"purple_stained_glass" => [5, 10],
			"blue_stained_glass" => [5, 10],
			"brown_stained_glass" => [5, 10],
			"green_stained_glass" => [5, 10],
			"red_stained_glass" => [5, 10],
			"black_stained_glass" => [5, 10],
		],
		self::CATEGORY_CONCRETE => [
			"white_concrete" => [7, 14],
			"orange_concrete" => [7, 14],
			"magenta_concrete" => [7, 14],
			"light_blue_concrete" => [7, 14],
			"yellow_concrete" => [7, 14],
			"lime_concrete" => [7, 14],
			"pink_concrete" => [7, 14],
			"gray_concrete" => [7, 14],
			"light_gray_concrete" => [7, 14],
			"cyan_concrete" => [7, 14],
			"purple_concrete" => [7, 14],
			"blue_concrete" => [7, 14],
			"brown_concrete" => [7, 14],
			"green_concrete" => [7, 14],
			"red_concrete" => [7, 14],
			"black_concrete" => [7, 14],

			"white_concrete_powder" => [5, 10],
			"orange_concrete_powder" => [5, 10],
			"magenta_concrete_powder" => [5, 10],
			"light_blue_concrete_powder" => [5, 10],
			"yellow_concrete_powder" => [5, 10],
			"lime_concrete_powder" => [5, 10],
			"pink_concrete_powder" => [5, 10],
			"gray_concrete_powder" => [5, 10],
			"light_gray_concrete_powder" => [5, 10],
			"cyan_concrete_powder" => [5, 10],
			"purple_concrete_powder" => [5, 10],
			"blue_concrete_powder" => [5, 10],
			"brown_concrete_powder" => [5, 10],
			"green_concrete_powder" => [5, 10],
			"red_concrete_powder" => [5, 10],
			"black_concrete_powder" => [5, 10],
		],
	];

	public static function getItemImage(Item $item): string {
		return "[REDACTED]" . $item->getName() . ".png";
	}
}
