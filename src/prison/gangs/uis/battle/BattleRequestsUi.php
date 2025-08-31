<?php namespace prison\gangs\uis\battle;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\Gang;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class BattleRequestsUi extends SimpleForm{

	public function __construct(Player $player, Gang $gang, string $message = "", $error = true){
		parent::__construct("Gang Battle Requests", ($message == "" ? "" : ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL) . "Select an option below!");

		$this->addButton(new Button("Send battle request"));
		$this->addButton(new Button("View received requests (" . count($gang->getBattleRequestManager()->getRequests()) . ")"));
		$this->addButton(new Button("View sent requests (" . count($gang->getBattleRequestManager()->getSentRequests()) . ")"));
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
		if($response == 0){
			$player->showModal(new SendBattleRequestUi($player, $gang));
			return;
		}
		if($response == 1){
			if(count($gang->getBattleRequestManager()->getRequests()) < 1){
				$player->showModal(new BattleRequestsUi($player, $gang, "Your gang has not received any battle requests!"));
				return;
			}
			$player->showModal(new ReceivedBattleRequestsUi($player, $gang));
			return;
		}
		if($response == 2){
			if(count($gang->getBattleRequestManager()->getSentRequests()) < 1){
				$player->showModal(new BattleRequestsUi($player, $gang, "Your gang has not sent any battle requests!"));
				return;
			}
			$player->showModal(new SentBattleRequestsUi($player, $gang));
			return;
		}
	}

}