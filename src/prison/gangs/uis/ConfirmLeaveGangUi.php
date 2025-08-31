<?php namespace prison\gangs\uis;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class ConfirmLeaveGangUi extends ModalWindow{

	public $chance;

	public function __construct(Player $player, $chance = false){
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		if($gang == null) return;

		$this->chance = $chance;

		parent::__construct("Are you " . ($chance ? "SURE you're sure?" : "sure?"), "Are you sure you want to leave your gang? This action can NOT be undone.", "Leave Gang", "Go back");
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gang = ($gm = Prison::getInstance()->getGangs()->getGangManager())->getPlayerGang($player);
		if($gang == null){
			$player->sendMessage(TextFormat::RI . "You are no longer in a gang.");
			return;
		}

		if($response){
			if($this->chance){
				$gang->getMemberManager()->removeMember($player->getXuid(), true, true);
				$gm->addLeftGang($player);
				$player->sendMessage(TextFormat::GI . "You are no longer in a gang!");
				return;
			}
			$player->showModal(new ConfirmLeaveGangUi($player, true));
			return;
		}
	}

}