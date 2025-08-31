<?php namespace prison\cells\ui;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\{
	Cell,
};

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class ConfirmCellClearUi extends ModalWindow{

	public $cell;

	public function __construct(Player $player, Cell $cell) {
		/** @var PrisonPlayer $player */
		$this->cell = $cell;

		parent::__construct("Clear Cell Style", "Are you sure you want to clear your current cell style?", "Clear Cell", "Go back");
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$cell = Prison::getInstance()->getCells()->getCellManager()->getCellByCell($this->cell);
		$hm = $cell->getHolderManager();
		if(!$hm->isOwner($player) && !$player->isTier3()){
			$player->sendMessage(TextFormat::RI . "You do not own this cell!");
			return;
		}
		if($response){
			$cell->clear(true);
			$player->sendMessage(TextFormat::GI . "Successfully cleared your cell design!");
		}
	}

}