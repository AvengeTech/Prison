<?php namespace prison\gangs\battle;

use pocketmine\item\ItemIds as Item;

class BattleKitData{

	const KITS = [
		"regular" => [
			"name" => "Regular",
			"items" => [
				["id" => 'netherite_sword', "enchantments" => [
					"oof" => 1
				]],
				["id" => 'bow'],
				["id" => 'arrow', "count" => 64],
				["id" => 'ender_pearl', "count" => 4],
				["id" => 'golden_apple', "count" => 16],
				["id" => 'enchanted_golden_apple', "count" => 1],
				["id" => 'cooked_rabbit', "count" => 64],
				//todo: totem?
			],
			"armor" => [
				0 => ["id" => 'netherite_helmet'],
				1 => ["id" => 'netherite_chestplate'],
				2 => ["id" => 'netherite_leggings'],
				3 => ["id" => 'netherite_boots'],
			]
		],
		"debuff" => [
			"name" => "De-Buff",
			"items" => [
				["id" => 'netherite_sword', "enchantments" => [
					"zeus" => 3,
					"bleed" => 3,
					"poison" => 2,
					"daze" => 3,
					"electrify" => 2,
					"frost" => 3,
				]],
				["id" => 'bow'],
				["id" => 'arrow', "count" => 64],
				["id" => 'ender_pearl', "count" => 5],
				["id" => 'golden_apple', "count" => 21],
				["id" => 'enchanted_golden_apple', "count" => 6],
				["id" => 'cooked_chicken', "count" => 64],
				["id" => 'snowball', "count" => 48],
			],
			"armor" => [
				0 => ["id" => 'iron_helmet', "enchantments" => [
					"protection" => 4,
					"unbreaking" => 3,
					"overlord" => 2,
					"glowing" => 1,
					"scorch" => 2,
				]],
				1 => ["id" => 'iron_chestplate', "enchantments" => [
					"unbreaking" => 1,
					"overlord" => 2,
					"adrenaline" => 1,
					"shockwave" => 2,
					"scorch" => 2,
				]],
				2 => ["id" => 'iron_leggings', "enchantments" => [
					"unbreaking" => 1,
					"overlord" => 2,
					"adrenaline" => 1,
					"shockwave" => 2,
					"scorch" => 2,
				]],
				3 => ["id" => 'iron_boots', "enchantments" => [
					"protection" => 4,
					"unbreaking" => 3,
					"overlord" => 2,
					"gears" => 1,
					"bunny" => 2,
					"scorch" => 2,
				]],
			]
		],
		"god" => [
			"name" => "God-like",
			"items" => [
				["id" => 'netherite_sword', "enchantments" => [
					"unbreaking" => 3,
					"kaboom" => 3,
					"zeus" => 3,
					"bleed" => 3,
					"pierce" => 3,
					"hades" => 2,
					"poison" => 3,
					"oof" => 2,
				]],
				["id" => 'bow'],
				["id" => 'arrow', "count" => 64],
				["id" => 'ender_pearl', "count" => 4],
				["id" => 'golden_apple', "count" => 64],
				["id" => 'enchanted_golden_apple', "count" => 16],
				["id" => 'cooked_beef', "count" => 64],
				["id" => 'snowball', "count" => 48],
			],
			"armor" => [
				0 => ["id" => 'netherite_helmet', "enchantments" => [
					"protection" => 2,
					"unbreaking" => 2,
					"blessing" => 2,
					"crouch" => 2,
					"sorcery" => 1,
					"rage" => 1,
					"shockwave" => 2,
					"adrenaline" => 1,
					"glowing" => 1,
					"scorch" => 4,
				]],
				1 => ["id" => 'netherite_chestplate', "enchantments" => [
					"protection" => 2,
					"unbreaking" => 2,
					"godly retribution" => 1,
					"overlord" => 2,
					"blessing" => 2,
					"crouch" => 2,
					"sorcery" => 1,
					"rage" => 1,
					"shockwave" => 2,
					"adrenaline" => 1,
					"scorch" => 4,
				]],
				2 => ["id" => 'netherite_leggings', "enchantments" => [
					"protection" => 2,
					"unbreaking" => 2,
					"overlord" => 2,
					"blessing" => 2,
					"crouch" => 2,
					"sorcery" => 1,
					"rage" => 1,
					"shockwave" => 2,
					"adrenaline" => 1,
					"scorch" => 4,
				]],
				3 => ["id" => 'netherite_boots', "enchantments" => [
					"protection" => 2,
					"unbreaking" => 2,
					"overlord" => 1,
					"blessing" => 2,
					"crouch" => 2,
					"gears" => 2,
					"sorcery" => 1,
					"rage" => 1,
					"bunny" => 3,
					"shockwave" => 2,
					"adrenaline" => 1,
					"scorch" => 4,
				]],
			]
		],
		"stick" => [
			"name" => "Stick Fight",
			"items" => [
				["id" => 'stick'],
			],
			"armor" => [],
		],

	];

}