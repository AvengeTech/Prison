<?php namespace prison\gangs\uis\battle;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\Gang;
use prison\gangs\battle\BattleRequest;

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class ConfirmNoTrophyBattleUi extends ModalWindow{

	public $request;

	public function __construct(Player $player, Gang $gang, BattleRequest $request){
		$this->request = $request;
		parent::__construct("Battle Request", "You have already battled this gang within the past hour, meaning you will not earn any trophies from it. Are you sure you would like to continue?", "Send Request", "Go back");
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
			$ogang = $request->getGang();
			if($ogang->inBattle()){
				$player->sendMessage(TextFormat::RI . "This gang is already in a battle! Please try again later");
				return;
			}
			if(!$ogang->getLeader()->isOnline()){
				$player->sendMessage(TextFormat::RI . "This gang's leader is no longer online!");
				return;
			}

			if(!$ogang->getBattleRequestManager()->addRequest($request)){
				$player->sendMessage(TextFormat::RI . "This gang already has a battle request from you!");
				return;
			}
			$player->sendMessage(TextFormat::GI . "Successfully sent a battle request to " . TextFormat::YELLOW . $ogang->getName() . "!");
			$ogang->getLeader()->getPlayer()->sendMessage(TextFormat::YI . "Your gang has received a battle request from " . TextFormat::RED . $gang->getName() . "! " . TextFormat::GRAY . "Type " . TextFormat::YELLOW . "/gang battle " . TextFormat::GRAY . "to view it!");
			return;
		}
		$player->showModal(new SendBattleRequestUi($player, $gang));
	}

}