<?php namespace prison\gangs\shop;

use prison\Prison;
use prison\gangs\GangManager;

class GangShop{

	public $gangManager;

	public $shops = [];

	public function __construct(GangManager $gangManager){
		$this->gangManager = $gangManager;
		$this->setupShop();
	}

	public function getGangManager() : GangManager{
		return $this->gangManager;
	}

	public function setupShop() : void{
		foreach(ShopData::SHOP_ITEMS as $level => $stock)
			$this->shops[$level] = new LevelShop($level, $stock);
	}

	public function getShops() : array{
		return $this->shops;
	}

	public function getShop(int $level = 1) : ?LevelShop{
		return $this->shops[$level] ?? null;
	}

}