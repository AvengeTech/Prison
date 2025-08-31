<?php namespace prison\gangs\uis\battle;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\battle\Battle;
use prison\gangs\objects\Gang;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class GangBattleUi extends SimpleForm{

	public $gang;
	public $battle;

	public function __construct(Player $player, Gang $gang, string $message = "", bool $error = true) {
		/** @var PrisonPlayer $player */
		$this->gang = $gang;
		$battle = $this->battle = Prison::getInstance()->getGangs()->getGangManager()->getBattleManager()->getBattleByGang($gang);
		if($battle !== null){
			$participants = $battle->getParticipantsFrom($gang);
			$pstr = "";
			foreach($participants as $pp){
				$pstr .= TextFormat::GRAY . "- " . TextFormat::YELLOW . $pp->getPlayer()->getName() . TextFormat::WHITE . PHP_EOL;
			}
			$op = $battle->getParticipantsFrom($battle->getOppositeGang($gang));
			$opstr = "";
			foreach($op as $pp){
				$opstr .= TextFormat::GRAY . "- " . TextFormat::LIGHT_PURPLE . $pp->getPlayer()->getName() . TextFormat::WHITE . PHP_EOL;
			}

			parent::__construct(
				"Gang Battles",
				($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
				"Your gang is currently in a battle!" . PHP_EOL . PHP_EOL .

				"Opposing Gang: " . TextFormat::RED . ($og = $battle->getOppositeGang($gang))->getName() . ($battle->isReady($og) ? " " . TextFormat::GREEN . "(READY!)" : "") . TextFormat::WHITE . PHP_EOL . 
				"Kit: " . TextFormat::YELLOW . $battle->getKit()->getName() . TextFormat::WHITE . PHP_EOL .
				"Mode: " . TextFormat::GREEN . $battle->getModeName() . TextFormat::WHITE . PHP_EOL . PHP_EOL . 

				"Max participants: " . TextFormat::DARK_RED . $battle->getMaxParticipants() . TextFormat::WHITE . PHP_EOL .
				"Your gang participants:" . PHP_EOL . $pstr . PHP_EOL .
				"Opposing gang participants:" . PHP_EOL . $opstr . PHP_EOL .
				"Battle status: " . TextFormat::AQUA . $battle->getStatusName() . TextFormat::WHITE . PHP_EOL . PHP_EOL .

				"Select an option below!"
			);


			switch($battle->getStatus()){
				case Battle::GAME_WAITING:
				case Battle::GAME_COUNTDOWN:
					if($gang->isLeader($player)){
						$this->addButton(new Button(($battle->isReady($gang) ? TextFormat::GREEN . "READY!" : TextFormat::RED . "NOT READY!") . TextFormat::DARK_GRAY . PHP_EOL . TextFormat::ITALIC . "Tap to toggle!"));
						$this->addButton(new Button("Cancel battle"));
					}else{
						$this->addButton(new Button($battle->isParticipating($player) ? "Leave battle" : "Join battle"));
					}
					break;

				case Battle::GAME_GET_READY:
				case Battle::GAME_START:
					if(!$battle->isParticipating($player)){
						$this->addButton(new Button("Spectate Battle"));
					}
					break;
			}
		}
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gm = Prison::getInstance()->getGangs()->getGangManager();
		$battle = $gm->getBattleManager()->getBattleByBattle($this->battle);
		if(!$gm->inGang($player)){
			$player->sendMessage(TextFormat::RI . "You are not in a gang!");
			return;
		}
		if($battle === null){
			$player->sendMessage(TextFormat::RI . "This battle no longer exists!");
			return;
		}
		$gang = $gm->getPlayerGang($player);
		if(!$battle->isGangInBattle($gang)){
			$player->sendMessage(TextFormat::RI . "This is not your gang battle! How tf did u manage to do that");
			return;
		}

		switch($battle->getStatus()){
			case Battle::GAME_WAITING:
			case Battle::GAME_COUNTDOWN:
				if($gang->isLeader($player)){
					if($response == 0){
						if(!$battle->areParticipantsEven()){
							$battle->setReady($gang, false);
							$player->showModal(new GangBattleUi($player, $gang, "Participant count for each gang must be equal before you can ready up!"));
							return;
						}
						$battle->setReady($gang, !$battle->isReady($gang));
						$player->showModal(new GangBattleUi($player, $gang, "Successfully toggled ready status.", false));
						return;
					}
					if($response == 1){
						$player->showModal(new CancelBattleConfirmUi($player, $gang, $battle));
						return;
					}
				}else{
					if($response == 0){
						if($battle->isReady($gang)){
							$player->sendMessage(TextFormat::RI . "Gang is already ready! You can no longer join this battle. However, you can watch it live with " . TextFormat::AQUA . "/gang battles");
							return;
						}
						if($battle->isParticipating($player)){
							$battle->removeParticipant($player);
							$player->showModal(new GangBattleUi($player, $gang, "You left the battle!", false));
						}else{
							$battle->addParticipant($player, $gang);
							$player->showModal(new GangBattleUi($player, $gang, "You joined the battle!", false));
						}
						return;
					}
				}
				break;

			case Battle::GAME_GET_READY:
			case Battle::GAME_START:

				break;
		}
	}

}