<?php namespace prison\gangs\uis\battle;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\Gang;
use prison\gangs\battle\BattleRequest;

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class CancelRequestConfirmUi extends ModalWindow{

	public $request;

	public function __construct(Player $player, Gang $gang, BattleRequest $request){
		$this->request = $request;

		parent::__construct("Cancel Battle Request", "Are you sure you would like to cancel your battle request to " . TextFormat::YELLOW . $request->getGang()->getName() . "?", "Cancel Request", "Go back");
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
			$request = $this->request;
			if(!$request->verify()){
				$player->sendMessage(TextFormat::RI . "This request no longer exists!");
				return;
			}
			$request->selfDestruct();
			$player->sendMessage(TextFormat::GI . "Successfully cancelled request sent to " . TextFormat::YELLOW . $request->getGang()->getName() . "!");
			return;
		}
		$player->showModal(new SentBattleRequestsUi($player, $gang));
	}

}