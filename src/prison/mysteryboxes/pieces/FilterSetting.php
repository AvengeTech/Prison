<?php

namespace prison\mysteryboxes\pieces;

class FilterSetting{

	public const FILTER_NONE = -1; // for cell layouts, tags, quest modules
	public const FILTER_ARMOR = 0;
	public const FILTER_BOOKS = 1;
	public const FILTER_BUILDING_BLOCKS = 2;
	public const FILTER_CUSTOM_ITEMS = 3; // Animators, Death Messages, Essence, Nametag, Mine Nukes, Haste Bombs 
	public const FILTER_DECORATION = 4;
	public const FILTER_FOOD = 5;
	public const FILTER_MISCELLANEOUS = 6;
	public const FILTER_ORES = 7;
	public const FILTER_TOOLS = 8;

	public function __construct(
		public int $type,
		public bool $value,
		public array $extraData = []
	){}

	public function getType() : int{ return $this->type; }

	public function getValue() : bool{ return $this->value; }

	public function setValue(bool $value) : self{
		$this->value = $value;

		return $this;
	}

	public function getExtraData() : array{ return $this->extraData; }

	/**
	 * If we plan on adding specifics too every filter setting
	 *
	 * @param array $extraData
	 *
	 * @return self
	 */
	public function setExtraData(array $extraData) : self{
		$this->extraData = $extraData;

		return $this;
	}
}