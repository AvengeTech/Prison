<?php namespace prison\shops\pieces;

use prison\shops\Structure;

use core\ui\elements\simpleForm\Button;

class Category{

	public $id;

	public $name;
	public $description;

	public $button;

	public $items = [];

	public function __construct(int $id){
		$this->id = $id;

		$this->name = Structure::CATEGORY_NAME[$id];
		$this->description = Structure::CATEGORY_DESCRIPTION[$id];

		$button = new Button($this->name . PHP_EOL . "Tap to open category");
		$button->addImage("url", Structure::CATEGORY_IMAGE[$id]);
		$this->button = $button;

		foreach (Structure::PRICES[$id] as $item => $_) {
			$this->items[$item] = new ShopItem($item, $id);
		}
	}

	public function getId() : string{
		return $this->id;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getDescription() : string{
		return $this->description;
	}

	public function getButton() : Button{
		return $this->button;
	}

	/** @return ShopItem[] */
	public function getItems() : array{
		return array_values($this->items);
	}

	public function getShopItem(string $item) : ?ShopItem{
		return $this->items[$item] ?? null;
	}

}