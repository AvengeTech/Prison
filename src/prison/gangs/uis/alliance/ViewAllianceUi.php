<?php namespace prison\gangs\uis\alliance;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\Alliance;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class ViewAllianceUi extends SimpleForm{

	public $ally;

	public function __construct(Player $player, Alliance $ally, $error = "") {
		/** @var PrisonPlayer $player */
		$this->ally = $ally;
		$ag = $ally->getAlly();

		parent::__construct(
			"Alliance Information",
			($error == "" ? "" : TextFormat::RED . "Error: " . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL) .
			"Name: " . $ag->getName() . PHP_EOL .
			"Description: " . $ag->getDescription() . PHP_EOL . PHP_EOL .
			"Level: " . $ag->getLevel() . PHP_EOL .
			"Trophies: " . $ag->getTrophies() . PHP_EOL . PHP_EOL .
			"Kills: " . $ag->getKills() . PHP_EOL .
			"Deaths: " . $ag->getDeaths() . PHP_EOL .
			"Blocks mined: " . $ag->getBlocks() . PHP_EOL . PHP_EOL .
			"Bank: " . $ag->getBankValue() . PHP_EOL . PHP_EOL .
			"Members: " . PHP_EOL . $ag->getMemberManager()->getOrderedMemberList() . PHP_EOL .
			"Select an option below for more information!"
		);

		$gm = Prison::getInstance()->getGangs()->getGangManager();
		$am = $gm->getAllianceManager();
		$gang = $gm->getPlayerGang($player);

		if($gang != null){
			if($gang->isLeader($player)){
				$this->addButton(new Button(TextFormat::RED . "Remove alliance"));
			}

			$this->addButton(new Button("Send a message"));
			$this->addButton(new Button("Go back"));
		}
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gm = Prison::getInstance()->getGangs()->getGangManager();
		$am = $gm->getAllianceManager();
		$gang = $gm->getPlayerGang($player);

		if($gang == null){
			$player->sendMessage(TextFormat::RI . "You are not in a gang!");
			return;
		}

		$ally = $this->ally;
		if(!$ally->verify()){
			$player->sendMessage(TextFormat::RI . "This alliance no longer exists!");
			return;
		}

		if($gang->isLeader($player)){
			if($response == 0){
				$player->showModal(new ConfirmAllianceDeleteUi($player, $ally));
				return;
			}
			if($response == 1){

				return;
			}
			if($response == 2){
				$player->showModal(new ViewAlliancesUi($player));
				return;
			}
		}else{
			if($response == 0){

				return;
			}
			if($response == 1){
				$player->showModal(new ViewAlliancesUi($player));
				return;
			}
		}

	}

}