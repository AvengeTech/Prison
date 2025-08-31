<?php namespace prison\quests\shop;

use prison\Prison;

class PointShop{

	public $plugin;
	public $categories = [];

	public function __construct(Prison $plugin){
		$this->plugin = $plugin;

		foreach (Structure::CATEGORY_PRIZES as $i => $_) {
			$this->categories[$i] = new Category($i);
		}
	}

	public function getCategories(){
		return $this->categories;
	}

	public function getCategory($id){
		return $this->categories[$id];
	}

}