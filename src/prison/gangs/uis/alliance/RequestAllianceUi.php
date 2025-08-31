<?php namespace prison\gangs\uis\alliance;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\Gang;

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Dropdown,
	Input
};
use core\utils\TextFormat;

class RequestAllianceUi extends CustomForm{

	public $gangs = [];

	public function __construct(Player $player, $error = "") {
		/** @var PrisonPlayer $player */
		parent::__construct("Request Alliance");

		$gm = Prison::getInstance()->getGangs()->getGangManager();
		$am = $gm->getAllianceManager();
		$gang = $gm->getPlayerGang($player);
		$aim = $gang->getAllianceInviteManager();

		$this->addElement(new Label(($error == "" ? "" : TextFormat::RED . "Error: " . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL) . "Select a gang you would like to send an alliance request to!"));
		$dd = new Dropdown("Online gangs:", ["Go back"]);
		foreach($gm->getGangs() as $g){
			if($g != $gang && (!$am->areAllies($gang->getId(), $g->getId())) && (!$aim->exists($g))){
				$this->gangs[] = $g;
				$dd->addOption($g->getName() . " - " . count($g->getMemberManager()->getMembers()) . "/" . $g->getMaxMembers());
			}
		}
		$this->addElement($dd);
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gm = Prison::getInstance()->getGangs()->getGangManager();
		$am = $gm->getAllianceManager();
		$gang = $gm->getPlayerGang($player);
		$aim = $gang->getAllianceInviteManager();

		if($gang == null){
			$player->sendMessage(TextFormat::RI . "You are not in a gang!");
			return;
		}

		$g = $response[1] - 1;
		if($g == -1){
			$player->showModal(new AllianceMainUi($player));
			return;
		}

		$g = $this->gangs[$g];
		if(!$gm->isLoaded($g)){
			$player->showModal(new RequestAllianceUi($player, "Gang selected is no longer online."));
			return;
		}

		if($am->areAllies($gang->getId(), $g->getId())){
			$player->showModal(new RequestAllianceUi($player, "You are already allied with this gang!"));
			return;
		}

		if($aim->exists($g)){
			$player->showModal(new RequestAllianceUi($player, "Your gang already has a pending request to this gang!"));
			return;
		}

		$player->showModal(new ConfirmAllianceRequestUi($player, $g));
	}

}