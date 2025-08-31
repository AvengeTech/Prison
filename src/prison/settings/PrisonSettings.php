<?php namespace prison\settings;

interface PrisonSettings{

	const VERSION = "1.0.0";

	//Normal
	const RAINBOW_BOSS_BAR = 1;
	const NO_TOOL_DROP = 2;
	const TOOL_BREAK_ALERT = 3;
	const LIGHTNING = 4;
	
	//Premium
	const MY_MYSTERY_BOX_MESSAGES = 50;
	const OTHER_MYSTERY_BOX_MESSAGES = 51;
	const AUTOSELL = 52;
	
	const DEFAULT_SETTINGS = [
		self::RAINBOW_BOSS_BAR => true,
		self::NO_TOOL_DROP => false,
		self::TOOL_BREAK_ALERT => true,
		self::LIGHTNING => true,

		self::MY_MYSTERY_BOX_MESSAGES => true,
		self::OTHER_MYSTERY_BOX_MESSAGES => true,
		self::AUTOSELL => false,
	];

	const SETTING_UPDATES = [

	];
	
}