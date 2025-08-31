<?php

namespace prison\mysteryboxes\pieces;

use stdClass;

class MysteryBoxFilter{

	public const MAX_INVENTORY_SIZE = 150;

	public const INVENTORY_SIZES = [
		"enderman" => 50,
		"wither" => 100,
		"enderdragon" => 150,
		"warden" => 1000
	];

	public const DEFAULT_SETTINGS = [
		FilterSetting::FILTER_ARMOR => false,
		FilterSetting::FILTER_BOOKS => false,
		FilterSetting::FILTER_BUILDING_BLOCKS => false,
		FilterSetting::FILTER_CUSTOM_ITEMS => false,
		FilterSetting::FILTER_DECORATION => false,
		FilterSetting::FILTER_FOOD => false,
		FilterSetting::FILTER_MISCELLANEOUS => false,
		FilterSetting::FILTER_ORES => false,
		FilterSetting::FILTER_TOOLS => false,
	];

	public function __construct(
		private bool $enabled = false,
		private array $settings = [],
		private bool $autoClear = false,
		private int $inventoryCount = 0,
		private int $inventoryValue = 0
	){
		if(!empty($settings)){
			foreach($settings as $settingID => $setting){
				if($setting instanceof stdClass) $this->settings[$settingID] = new FilterSetting($setting->type, $setting->value, $setting->extraData);
			}
		}else{
			foreach(self::DEFAULT_SETTINGS as $settingID => $settingValue){
				$this->settings[$settingID] = new FilterSetting($settingID, $settingValue);
			}
		}

	}

	public function isEnabled() : bool{
		return $this->enabled;
	}

	public function setEnabled(bool $value) : self{
		$this->enabled = $value;

		return $this;
	}

	public function isAutoClearing() : bool{
		return $this->autoClear;
	}

	public function setAutoClear(bool $value) : self{
		$this->autoClear = $value;

		return $this;
	}

	public function getSetting(int $type) : FilterSetting{
		return $this->settings[$type];
	}

	/**
	 * @return FilterSetting[]
	 */
	public function getSettings() : array{
		return $this->settings;
	}

	public function setSettings(array $settings) : self{
		$this->settings = $settings;

		return $this;
	}

	public function addInventoryValue(int $value) : self{
		$this->inventoryValue += $value;
		return $this;
	}

	public function setInventoryValue(int $value) : self{
		$this->inventoryValue = $value;
		return $this;
	}

	public function subInventoryValue(int $value) : self{
		$this->inventoryValue -= $value;
		return $this;
	}

	public function getInventoryValue() : int{
		return $this->inventoryValue;
	}

	public function increaseCount(int $count) : self{
		$this->inventoryCount += $count;
		return $this;
	}

	public function setCount(int $count) : self{
		$this->inventoryCount = $count;
		return $this;
	}

	public function getCount() : int{
		return $this->inventoryCount;
	}

	public function getSize(string $rank) : int{
		return self::INVENTORY_SIZES[$rank] ?? self::MAX_INVENTORY_SIZE;
	}

	public function isFull(string $rank) : int{
		return $this->inventoryCount >= $this->getSize($rank);
	}
}