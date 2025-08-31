<?php namespace prison\gangs\uis\battle;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\Gang;
use prison\gangs\battle\Battle;

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class CancelBattleConfirmUi extends ModalWindow{

	public $battle;

	public function __construct(Player $player, Gang $gang, Battle $battle){
		$this->battle = $battle;
		parent::__construct("Cancel Battle", "Are you sure you would like to cancel your battle with " . TextFormat::YELLOW . $battle->getOppositeGang($gang)->getName() . "?", "Cancel Battle", "Go back");
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gm = Prison::getInstance()->getGangs()->getGangManager();
		if(!$gm->inGang($player)){
			$player->sendMessage(TextFormat::RI . "You are not in a gang!");
			return;
		}
		$gang = $gm->getPlayerGang($player);
		if(!$gang->isLeader($player)){
			$player->sendMessage(TextFormat::RI . "You must be gang leader to manage battle requests!");
			return;
		}
		if($response){
			$battle = $gm->getBattleManager()->getBattleByBattle($this->battle);
			if($battle === null){
				$player->sendMessage(TextFormat::RI . "This battle no longer exists!");
				return;
			}
			$battle->cancel("Leader of " . TextFormat::YELLOW . $gang->getName() . TextFormat::GRAY . " has cancelled the battle");
			$player->sendMessage(TextFormat::GI . "Successfully cancelled the battle you were in!");
			return;
		}
		$player->showModal(new GangBattleUi($player, $gang));
	}

}