<?php namespace prison\gangs\uis\battle;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\Gang;
use prison\gangs\battle\BattleRequest;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class BattleRequestUi extends SimpleForm{

	public $request;

	public function __construct(Player $player, Gang $gang, BattleRequest $request){
		$this->request = $request;

		parent::__construct(
			"Battle Request",
			"You have received a battle request from " . TextFormat::RED . $request->getRequesting()->getName() . TextFormat::WHITE . PHP_EOL . PHP_EOL .
			"Kit: " . TextFormat::YELLOW . $request->getKit()->getName() . TextFormat::WHITE . PHP_EOL .
			"Mode: " . TextFormat::GREEN . $request->getModeName() . TextFormat::WHITE . PHP_EOL . PHP_EOL .
			"Maximum participants: " . TextFormat::DARK_RED . $request->getMaxParticipants() . TextFormat::WHITE . PHP_EOL . PHP_EOL . 
			"Once you accept a battle request, gang members from each side will be able to enter the battle by typing " . TextFormat::AQUA . "/gang battle" . TextFormat::WHITE . PHP_EOL . PHP_EOL . "Both teams must have an even amount of participants before the leader can " . TextFormat::GREEN . "ready up" . TextFormat::WHITE . " (fortnite moment)" . PHP_EOL . PHP_EOL .
			($gang->getAllianceManager()->areAllies($request->getGang(), $request->getRequesting()) ? "(NOTE: Your gang is allied with this gang, meaning no trophies or battle stats will be earned.)" : "") .
			"Select an option below!"
		);

		$this->addButton(new Button("Accept request"));
		$this->addButton(new Button("Decline request"));
		$this->addButton(new Button("Go back"));
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

		$request = $this->request;
		if($response == 0 || $response == 1){
			if(!$request->verify()){
				$player->sendMessage(TextFormat::RI . "This battle request no longer exists!");
				return;
			}
		}
		if($response == 0){
			if(!$request->accept()){
				$player->sendMessage(TextFormat::RI . "Battle request could not be accepted!");
				return;
			}
			$player->sendMessage(TextFormat::GI . "Good luck!");
			return;
		}
		if($response == 1){
			$request->decline();
			if(count($gang->getBattleRequestManager()->getRequests()) > 1){
				$player->showModal(new ReceivedBattleRequestsUi($player, $gang, "Successfully declined battle request!", false));
			}else{
				$player->showModal(new BattleRequestsUi($player, $gang, "Successfully declined battle request!", false));
			}
			return;
		}
		if($response == 0){
			$player->showModal(new ReceivedBattleRequestsUi($player, $gang));
			return;
		}
	}

}