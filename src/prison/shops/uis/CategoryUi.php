<?php namespace prison\shops\uis;

use pocketmine\player\Player;

use prison\shops\pieces\Category;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use core\utils\TextFormat;
use prison\PrisonPlayer;

class CategoryUi extends SimpleForm{

	public $category;

	public function __construct(Category $category, $error = ""){
		parent::__construct($category->getName(), ($error !== "" ? TextFormat::RED . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") . $category->getDescription());

		$this->category = $category;

		foreach($category->getItems() as $item){
			if ($item->isValid()) $this->addButton($item->getButton());
		}
		$this->addButton(new Button("Back"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$items = $this->category->getItems();
		if($response == count($items)){
			$player->showModal(new CategorySelectUi());
			return;
		}
		$item = $items[$response];
		$player->showModal($item->getTradeUi($player));
	}

}