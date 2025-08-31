<?php namespace prison\gangs\uis\alliance;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\{
	Gang,
	AllianceInvite
};

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class OutgoingAllianceRequestUi extends SimpleForm{

	public $invite;

	public function __construct(Player $player, AllianceInvite $invite, $error = "") {
		/** @var PrisonPlayer $player */
		$gm = Prison::getInstance()->getGangs()->getGangManager();
		$gang = $gm->getPlayerGang($player);
		$aim = $gang->getAllianceInviteManager();

		$this->invite = $invite;
		$g = $invite->getGang();
		$message = $invite->getMessage();

		parent::__construct("View Alliance Request", ($error == "" ? "" : TextFormat::RED . "Error: " . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL) . "Gang Information:" . PHP_EOL . PHP_EOL .
			"Sent to: " . $g->getName() . PHP_EOL .
			"Description: " . $g->getDescription() . PHP_EOL .
			"Created by: " . $g->getLeader()->getGamertag() . PHP_EOL . PHP_EOL .

			"Level: " . $g->getLevel() . PHP_EOL .
			"Trophies: " . $g->getTrophies() . PHP_EOL . PHP_EOL .

			"Total Members: " . count($g->getMemberManager()->getMembers()) . "/" . $g->getMaxMembers() . PHP_EOL . PHP_EOL .

			"Total kills: " . $g->getKills() . PHP_EOL .
			"Total deaths: " . $g->getDeaths() . PHP_EOL .
			"Total blocks mined: " . $g->getBlocks() . PHP_EOL . PHP_EOL .

			($message != "" ? "Message left by sender (" . $invite->getUser()->getGamertag() . "):" . PHP_EOL .
			" - " . $message . PHP_EOL . PHP_EOL : "") . 

			"Select an option below."
		);

		if($gang->isLeader($player) || $invite->getUser()->getPlayer() == $player){
			$this->addButton(new Button("Cancel request"));
		}
		$this->addButton(new Button("Go back"));
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

		$invite = $this->invite;
		if(!$invite->verify()){
			$player->showModal(new ViewOutgoingAllianceRequestsUi($player, "Invite no longer exists."));
			return;
		}

		if((!$gang->isLeader($player) && ($invite->getUser()->getPlayer() != $player)) && $response == 0){
			$player->showModal(new ViewOutgoingAllianceRequestsUi($player));
			return;
		}

		if($response == 0){
			$invite->cancel();
			$player->sendMessage(TextFormat::GI . "Alliance request sent to this gang has been cancelled!");
			return;
		}
		if($response == 1){
			$player->showModal(new ViewOutgoingAllianceRequestsUi($player));
			return;
		}
	}

}