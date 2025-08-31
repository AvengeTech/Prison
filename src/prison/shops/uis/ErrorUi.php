<?php namespace prison\shops\uis;

use pocketmine\player\Player;

use prison\shops\pieces\ShopItem;
use prison\Prison;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use prison\PrisonPlayer;

class ErrorUi extends SimpleForm{

	public $returnId;

	public function __construct($returnId, $errormsg = "An unknown error occured whilst processing your transaction."){
		parent::__construct("Error", $errormsg);
		$this->addButton(new Button("Back"));

		$this->returnId = $returnId;
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$player->showModal(new CategoryUi(Prison::getInstance()->getShops()->getCategoryById($this->returnId)));
	}

}