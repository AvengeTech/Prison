<?php namespace prison\gangs\uis;

use pocketmine\player\Player;

use prison\Prison;
use prison\gangs\objects\GangMember;

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;
use prison\PrisonPlayer;

class UpgradeGangUi extends ModalWindow{

	public function __construct(Player $player){
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		if($gang == null) return;

		$c = $gang::LEVEL_CHART[min(max(1, $gang->getLevel() + 1), 5)];
		$level = $gang->getLevel() + 1;
		parent::__construct("Are you sure?", "Are you sure you want to upgrade your gang to level " . TextFormat::YELLOW . $level . TextFormat::WHITE . "?" . PHP_EOL . PHP_EOL . "Trophies needed: " . ($gang->getTrophies() >= $c["trophies"] ? TextFormat::AQUA : TextFormat::RED) . number_format($c["trophies"]) . TextFormat::WHITE . ", Techits needed: " . ($gang->getBankValue() >= $c["techits"] ? TextFormat::AQUA : TextFormat::RED) . number_format($c["techits"]), "Upgrade gang", "Go back");
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		if($gang == null){
			$player->sendMessage(TextFormat::RI . "You are no longer in a gang.");
			return;
		}

		if($response){
			if($gang->getLevel() >= $gang::MAX_LEVEL){
				$player->sendMessage(TextFormat::RI . "Your gang is already the max level and can no longer be upgraded!");
				return;
			}
			if(!$gang->canLevelUp() && !$player->isTier3()){
				$c = $gang::LEVEL_CHART[min(max(1, $gang->getLevel() + 1), 5)];
				$player->sendMessage(TextFormat::RI . "Your gang does not meet the requirements to upgrade! Trophies needed: " . ($gang->getTrophies() >= $c["trophies"] ? TextFormat::AQUA : TextFormat::RED) . number_format($c["trophies"]) . TextFormat::GRAY . ", Techits needed: " . ($gang->getBankValue() >= $c["techits"] ? TextFormat::AQUA : TextFormat::RED) . number_format($c["techits"]));
				return;
			}
			$gang->levelUp();
			$player->sendMessage(TextFormat::GI . "Your gang is now level " . TextFormat::YELLOW . $gang->getLevel() . TextFormat::GRAY . "!");
			return;
		}

	}

}