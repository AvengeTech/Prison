<?php namespace prison\quests\shop;

use core\ui\elements\simpleForm\Button;

class Category{

	public $id;

	public $items = [];

	public function __construct($id){
		$this->id = $id;

		$items = Structure::CATEGORY_PRIZES[$id];
		foreach($items as $data => $price){
			$this->items[] = new ShopItem($data, $price);
		}
	}

	public function getId(){
		return $this->id;
	}

	public function getItems(){
		return $this->items;
	}

	public function getName(){
		return Structure::CATEGORY_NAMES[$this->getId()];
	}

	public function getButton(){
		return new Button($this->getName() . PHP_EOL . "Tap to view category!");
	}

}