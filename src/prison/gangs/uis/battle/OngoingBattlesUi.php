<?php namespace prison\gangs\uis\battle;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\battle\Battle;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class OngoingBattlesUi extends SimpleForm{

	public $battles = [];

	public function __construct(Player $player, string $message = "", bool $error = true){
		parent::__construct("Ongoing Battles", "Tap a battle that you'd like to spectate below!");

		foreach(Prison::getInstance()->getGangs()->getGangManager()->getBattleManager()->getBattles() as $battle){
			if($battle->getStatus() > Battle::GAME_COUNTDOWN){
				$this->battles[] = $battle;
				$this->addButton(new Button($battle->getGang1()->getName() . " vs. " . $battle->getGang2()->getName() . PHP_EOL . "Status: " . $battle->getStatusName()));
			}
		}
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$bm = Prison::getInstance()->getGangs()->getGangManager()->getBattleManager();
		$battle = $bm->getBattleByBattle($this->battles[$response]);
		if($battle === null){
			$player->showModal(new OngoingBattlesUi($player, "This battle has ended!"));
			return;
		}
		if($battle->getStatus() <= Battle::GAME_COUNTDOWN){
			$player->showModal(new OngoingBattlesUi($player, "This battle has not started yet!"));
			return;
		}
		$session = $player->getGameSession()->getMines();
		if($session->inMine()){
			$session->exitMine(false);
		}
		if($bm->isSpectator($player)){
			$bm->getSpectating($player)->remove();
		}
		$battle->addSpectator($player);
		$player->sendMessage(TextFormat::RI . "You are now in spectator mode.");
	}

}