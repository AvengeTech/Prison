<?php namespace prison\guards\ui;

use pocketmine\player\Player;

use prison\PrisonPlayer;
use prison\guards\Bin;

use core\ui\windows\ModalWindow;

class ConfirmDeleteBinUi extends ModalWindow{
	
	public function __construct(Player $player, public Bin $bin){
		parent::__construct("Delete Bin", "Are you sure you want to dispose of this bin? All items left inside will be lost!", "Delete Bin", "Go back");
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$bin = $this->bin;
		$session = $player->getGameSession()->getGuards();
		
		if($response){
			$session->removeBin($bin);
			$player->showModal(new ShowBinsUi($player, "Successfully disposed of bin!", false));
			return;
		}
		$player->showModal(new BinUi($player, $bin));
	}

}