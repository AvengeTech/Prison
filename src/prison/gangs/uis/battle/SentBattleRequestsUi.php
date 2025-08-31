<?php namespace prison\gangs\uis\battle;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\Gang;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class SentBattleRequestsUi extends SimpleForm{

	public $requests = [];

	public function __construct(Player $player, Gang $gang, string $message = "", $error = true){
		parent::__construct("Sent Battle Requests", ($message == "" ? "" : ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL) . "Tap on a battle request below to cancel!");

		$requests = $gang->getBattleRequestManager()->getSentRequests();
		foreach($requests as $request){
			$this->requests[] = $request;
			$this->addButton(new Button($request->getGang()->getName() . PHP_EOL . TextFormat::ITALIC . "Tap to cancel"));
		}
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

		$request = $this->requests[$response] ?? null;
		if($request === null){
			$player->showModal(new BattleRequestsUi($player, $gang));
			return;
		}
		if(!$request->verify()){
			$player->showModal(new SentBattleRequestsUi($player, $gang, "This battle request no longer exists!"));
			return;
		}

		$player->showModal(new CancelRequestConfirmUi($player, $gang, $request));
	}

}