<?php namespace prison\mysteryboxes;

use pocketmine\item\VanillaItems;
use prison\mysteryboxes\pieces\FilterSetting;

class Structure{

	const RARITY_COMMON = 0;
	const RARITY_UNCOMMON = 1;
	const RARITY_RARE = 2;
	const RARITY_LEGENDARY = 3;
	const RARITY_DIVINE = 4;
	const RARITY_VOTE = 5;

	const BOX_LOCATIONS = [
		[-863, 25, 397, "iron", "newpsn"],
		[-870, 25, 396, "iron", "newpsn"],
		[-875, 25, 390, "gold", "newpsn"],
		[-875, 25, 376, "diamond", "newpsn"],
		[-870, 25, 370, "emerald", "newpsn"],
		[-863, 25, 369, "vote", "newpsn"],
		[-865, 25, 383, "divine", "newpsn"],
	];

	const PRIZES_NEW = [
		self::RARITY_COMMON => [
			//Tools
			"i:golden_helmet" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_ARMOR], //Gold Helmet
			"i:golden_chestplate" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_ARMOR], //Gold Chestplate
			"i:golden_leggings" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_ARMOR], //Gold Leggings
			"i:golden_boots" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_ARMOR], //Gold Boots
			"i:golden_sword" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_TOOLS], //Gold Sword
			"i:golden_pickaxe" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_TOOLS], //Gold Pickaxe
			"i:chainmail_helmet" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_ARMOR], //Chain Helmet
			"i:chainmail_chestplate" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_ARMOR], //Chain Chestplate
			"i:chainmail_leggings" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_ARMOR], //Chain Leggings
			"i:chainmail_boots" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_ARMOR], //Chain Boots

			//Foods
			"i:raw_chicken:16" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_FOOD],
			"i:raw_porkchop:16" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_FOOD],
			"i:raw_beef:16" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_FOOD],
			"i:bread:16" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_FOOD],
			"i:cooked_chicken:8" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_FOOD],
			"i:steak:8" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_FOOD],
			"i:cooked_porkchop:8" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_FOOD],

			"i:coal:8" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_ORES], //Coal
			"i:oak_planks:16" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_BUILDING_BLOCKS], //Oak wood
			"i:granite:16" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_BUILDING_BLOCKS], //Granite
			"i:diorite:16" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_BUILDING_BLOCKS], //Diorite
			"i:andesite:16" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_BUILDING_BLOCKS], //Andesite

			//Colored Stuff
			"i:concrete_powder:4" => ["subRarity" => 2, "filter" => FilterSetting::FILTER_BUILDING_BLOCKS],
			"i:concrete:4" => ["subRarity" => 2, "filter" => FilterSetting::FILTER_BUILDING_BLOCKS],
			"i:stained_glass:4" => ["subRarity" => 2, "filter" => FilterSetting::FILTER_BUILDING_BLOCKS],
			"i:dye:4" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_MISCELLANEOUS],

			"i:experience_bottle:8" => ["subRarity" => 3, "filter" => FilterSetting::FILTER_MISCELLANEOUS], //Bottle o' Enchanting
		],
		self::RARITY_UNCOMMON => [
			//Food
			"i:apple:16" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_FOOD],
			"i:steak:32" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_FOOD],
			"i:cooked_chicken:32" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_FOOD],
			"i:cooked_porkchop:32" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_FOOD],
			"i:cooked_mutton:32" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_FOOD],
			"i:golden_apple:4" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_FOOD],

			"i:bow" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_TOOLS], //Bow
			"i:arrow:64" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_MISCELLANEOUS], //Arrows
			"i:shears" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_TOOLS], //Shears

			"i:stone:32" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_BUILDING_BLOCKS], //Stone
			"i:obsidian:8" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_BUILDING_BLOCKS], //Obsidian
			"i:grass:16" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_BUILDING_BLOCKS], //Grass Block
			"i:oak_sapling" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_DECORATION], //Saplings

			//Armor + Tools
			"i:iron_pickaxe" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_TOOLS], //Iron Pickaxe
			"i:iron_axe" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_TOOLS], //Iron Axe
			"i:iron_shovel" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_TOOLS], //Iron Shovel
			"i:iron_hoe" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_TOOLS], //Iron Hoe
			"i:iron_sword" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_TOOLS], //Iron Sword
			"i:iron_helmet" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_ARMOR], //Iron Helmet
			"i:iron_chestplate" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_ARMOR], //Iron Chestplate
			"i:iron_leggings" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_ARMOR], //Iron Leggings
			"i:iron_boots" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_ARMOR], //Iron Boots

			"i:armor_stand" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_DECORATION], //Armor Stand

			//Colored Stuff
			"i:concrete_powder:16" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_BUILDING_BLOCKS],
			"i:concrete:16" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_BUILDING_BLOCKS],
			"i:stained_glass:16" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_BUILDING_BLOCKS],
			"i:dye:16" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_MISCELLANEOUS],

			"i:experience_bottle:8" => ["subRarity" => 2, "filter" => FilterSetting::FILTER_MISCELLANEOUS], //Bottle o' Enchanting
			"i:redeemable_book:1:1:1:0" => ["subRarity" => 2, "filter" => FilterSetting::FILTER_BOOKS], // Common Random Book
			"i:redeemable_book:1:2:1:0" => ["subRarity" => 3, "filter" => FilterSetting::FILTER_BOOKS], // Common Max Book
			"i:redeemable_book:1:3:-1:0" => ["subRarity" => 3, "filter" => FilterSetting::FILTER_BOOKS], // Random Rarity Book
			"i:essence_of_success:1:1" => ["subRarity" => 2, "filter" => FilterSetting::FILTER_CUSTOM_ITEMS], // Common Essence of Success
		],
		self::RARITY_RARE => [
			//Armor + Tools
			"i:diamond_axe" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_TOOLS], //Diamond Axe
			"i:diamond_pickaxe" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_TOOLS], //Diamond Pickaxe

			//Food
			"i:golden_apple:8" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_FOOD],
			"i:carrot:8" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_FOOD],
			"i:potato:8" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_FOOD],

			//Minerals
			"i:diamond:16" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_ORES],
			"i:emerald:16" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_ORES],
			"i:obsidian:16" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_BUILDING_BLOCKS],
			"i:vines:16" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_DECORATION],

			"i:diamond:32" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_ORES],
			"i:emerald:32" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_ORES],
			"i:obsidian:32" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_BUILDING_BLOCKS],
			"i:vines:32" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_DECORATION],

			//Colored Stuff
			"i:concrete_powder:32" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_BUILDING_BLOCKS],
			"i:concrete:32" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_BUILDING_BLOCKS],
			"i:stained_glass:32" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_BUILDING_BLOCKS],
			"i:dye:32" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_MISCELLANEOUS],

			//Enchantment books
			"i:experience_bottle:16" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_MISCELLANEOUS], //Bottle o' Enchanting
			"i:redeemable_book:1:1:2:0" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_BOOKS], // Uncommon Random Book
			"i:redeemable_book:1:2:2:0" => ["subRarity" => 3, "filter" => FilterSetting::FILTER_BOOKS], // Uncommon Max Book
			"i:redeemable_book:1:3:-1:0" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_BOOKS], // Random Rarity Book
			"i:essence_of_success:1:2" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_CUSTOM_ITEMS], // Uncommon Essence of Success
			"i:essence_of_success:1:3" => ["subRarity" => 2, "filter" => FilterSetting::FILTER_CUSTOM_ITEMS], // Rare Essence of Success
			"i:nametag" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_CUSTOM_ITEMS], //Nametag
			"i:death_tag" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_CUSTOM_ITEMS], //Custom Death Tag

			"pvf:tag" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_NONE],
			// "pvf:cl" => ["subRarity" => 2, "filter" => FilterSetting::FILTER_NONE], //Cell Layouts // CRASH THE SERVER, LAYOUTS MIGHT NOT BE SETUP?
		],
		self::RARITY_LEGENDARY => [
			"i:enchanted_golden_apple:4" => ["subRarity" => 3, "filter" => FilterSetting::FILTER_FOOD], //Enchanted Golden Apples
			"i:golden_apple:8" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_FOOD], //Golden Apples
			"i:golden_apple:12" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_FOOD], //Golden Apples

			//Enchantment
			"i:experience_bottle:32" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_MISCELLANEOUS], //Bottle o' Enchanting 
			"i:experience_bottle:64" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_MISCELLANEOUS], //Bottle o' Enchanting
			"i:redeemable_book:1:1:3:0" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_BOOKS], // Rare Random Book
			"i:redeemable_book:1:2:3:0" => ["subRarity" => 2, "filter" => FilterSetting::FILTER_BOOKS], // Rare Max Book
			"i:redeemable_book:1:1:4:0" => ["subRarity" => 2, "filter" => FilterSetting::FILTER_BOOKS], // Legendary Random Book
			"i:redeemable_book:1:2:4:0" => ["subRarity" => 3, "filter" => FilterSetting::FILTER_BOOKS], // Legendary Max Book
			"i:redeemable_book:1:3:-1:1" => ["subRarity" => 3, "filter" => FilterSetting::FILTER_BOOKS], // Random Rarity Book include Divine
			"i:effect_item" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_CUSTOM_ITEMS], //Animator
			"i:mine_nuke" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_CUSTOM_ITEMS], //Mine Nuke
			"i:mine_nuke:2" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_CUSTOM_ITEMS], //Mine Nuke
			"i:essence_of_success:1:4" => ["subRarity" => 2, "filter" => FilterSetting::FILTER_CUSTOM_ITEMS], // Legendary Essence of Success
			"i:essence_of_knowledge:1:4" => ["subRarity" => 2, "filter" => FilterSetting::FILTER_CUSTOM_ITEMS], // Essence of Knowledge(no rarities, but has it just in case)
			"i:essence_of_progress:1:4" => ["subRarity" => 4, "filter" => FilterSetting::FILTER_CUSTOM_ITEMS], // Essence of Progress(no rarities, but has it just in case)

			"module:0" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_NONE], //Quest Modules

			"pvf:tag" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_NONE],
			"i:unbound_tome:1:25" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_CUSTOM_ITEMS],
			"i:unbound_tome:1:50" => ["subRarity" => 2, "filter" => FilterSetting::FILTER_CUSTOM_ITEMS],
		],
		self::RARITY_DIVINE => [
			"pvf:kp:1:medium" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_NONE],
			
			"i:enchanted_golden_apple:128" => ["subRarity" => 2, "filter" => FilterSetting::FILTER_FOOD], //Egap
			"i:haste_bomb:32" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_CUSTOM_ITEMS], //Haste bomb
			"i:mine_nuke:64" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_CUSTOM_ITEMS], //Mine nukes

			"i:redeemable_book:1:1:5:1" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_BOOKS], //Divine Book
			"i:redeemable_book:2:1:5:1" => ["subRarity" => 2, "filter" => FilterSetting::FILTER_BOOKS], //Divine Book
			"i:redeemable_book:1:2:5:0" => ["subRarity" => 3, "filter" => FilterSetting::FILTER_BOOKS], // Divine Max Book
		],
		self::RARITY_VOTE => [
			"i:essence_of_ascension:1:1" => ["subRarity" => 0, "filter" => FilterSetting::FILTER_CUSTOM_ITEMS], // Common Essence of Ascension
			"i:essence_of_ascension:1:2" => ["subRarity" => 1, "filter" => FilterSetting::FILTER_CUSTOM_ITEMS], // Uncommon Essence of Ascension
			"i:essence_of_ascension:1:3" => ["subRarity" => 2, "filter" => FilterSetting::FILTER_CUSTOM_ITEMS], // Rare Essence of Ascension
			"i:essence_of_ascension:1:4" => ["subRarity" => 3, "filter" => FilterSetting::FILTER_CUSTOM_ITEMS], // Legendary Essence of Ascension
		]
	];

}