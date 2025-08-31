<?php namespace prison\gangs\uis\alliance;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\{
	Gang,
	AllianceInvite
};

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Input,
	Toggle
};
use core\utils\TextFormat;

class ConfirmAllianceRequestUi extends CustomForm{

	public $gang;

	public function __construct(Player $player, Gang $g, $error = "") {
		/** @var PrisonPlayer $player */
		parent::__construct("Confirm Alliance Request");

		$this->gang = $g;

		$gm = Prison::getInstance()->getGangs()->getGangManager();
		$am = $gm->getAllianceManager();
		$gang = $gm->getPlayerGang($player);

		$this->addElement(new Label(($error == "" ? "" : TextFormat::RED . "Error: " . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL) . "Gang information:"));
		$this->addElement(new Input("Enter a message to show the gang you'd like to ally with! (optional)", "blah blah"));
		$this->addElement(new Toggle("Check this box to confirm your alliance request!"));
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

		$g = $this->gang;
		if(!$gm->isLoaded($g)){
			$player->showModal(new RequestAllianceUi($player, "Gang selected is no longer online."));
			return;
		}
		$g = $gm->getGangById($g->getId());

		if(count($g->getAlliances()) >= Gang::MAX_ALLIANCES && !$player->isTier3()){
			$player->showModal(new RequestAllianceUi($player, "This gang has reached the max amount of alliances allowed!"));
			return;
		}

		$message = $response[1];
		if(!$response[2]){
			$player->showModal(new ConfirmAllianceRequestUi($player, $g, "You must check the box at the bottom to confirm! Please try again."));
			return;
		}

		$invite = new AllianceInvite($g->getId(), $gang->getId(), $player->getUser(), $message);
		if(!$g->getAllianceInviteManager()->addInvite($invite, true)){
			$player->showModal(new AllianceMainUi($player, "An error occured when sending this alliance request. Please try again later!"));
			return;
		}
		$player->sendMessage(TextFormat::GI . "Sent an alliance request to " . TextFormat::YELLOW . $g->getName() . TextFormat::GRAY . "!");
	}

}