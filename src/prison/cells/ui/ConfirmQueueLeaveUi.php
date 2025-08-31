<?php namespace prison\cells\ui;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\{
	Cell,
};

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class ConfirmQueueLeaveUi extends ModalWindow{

	public $cell;

	public function __construct(Player $player, Cell $cell) {
		/** @var PrisonPlayer $player */
		$this->cell = $cell;

		parent::__construct("Leave Cell Queue", "Are you sure you want to leave this cell queue? You will be refunded your queue fee of " . TextFormat::AQUA . $cell->getStartingRent() . " Techits", "Leave Queue", "Go back");
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$cell = Prison::getInstance()->getCells()->getCellManager()->getCellByCell($this->cell);
		if(!($hm = $cell->getHolderManager())->isHolder($player)){
			$player->sendMessage(TextFormat::RI . "You are not in this cell queue!");
			return;
		}
		if($hm->isOwner($player)){
			$player->sendMessage(TextFormat::RI . "You already own this cell now! Please wait for it to expire");
			return;
		}
		if($response){
			$hm->removeHolder($hm->getHolderBy($player));
			$player->addTechits($cell->getStartingRent());
			$player->showModal(new CellInfoUi($player, $cell, "Successfully left cell queue! Your techits were refunded.", false));
		}else{
			$player->showModal(new CellInfoUi($player, $cell));
		}
	}

}