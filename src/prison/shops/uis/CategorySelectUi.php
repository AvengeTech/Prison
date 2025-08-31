<?php namespace prison\shops\uis;

use pocketmine\player\Player;

use prison\Prison;

use core\ui\windows\SimpleForm;
use prison\PrisonPlayer;

class CategorySelectUi extends SimpleForm{

	public function __construct(){
		parent::__construct("Black Market", "Whatcha lookin for today, prisoner...");

		$categories = Prison::getInstance()->getShops()->getCategories();

		foreach($categories as $catId => $category){
			$this->addButton($category->getButton());
		}
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$categories = Prison::getInstance()->getShops()->getCategories();
		$category = $categories[$response];

		$player->showModal(new CategoryUi($category));
	}

}