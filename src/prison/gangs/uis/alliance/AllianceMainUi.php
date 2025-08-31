<?php namespace prison\gangs\uis\alliance;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\Gang;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class AllianceMainUi extends SimpleForm{

	public function __construct(Player $player, string $error = "") {
		/** @var PrisonPlayer $player */
		parent::__construct("Gang alliances", ($error == "" ? "" : TextFormat::RED . "Error: " . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL) . "Select an option below for more information!");

		$gm = Prison::getInstance()->getGangs()->getGangManager();
		$am = $gm->getAllianceManager();
		$gang = $gm->getPlayerGang($player);
		$aim = $gang->getAllianceInviteManager();

		if($gang != null){
			$this->addButton(new Button("Request alliance"));
			$this->addButton(new Button("View alliances (" . count($am->getAlliances($gang)) . "/" . Gang::MAX_ALLIANCES . ")"));
			$this->addButton(new Button("Incoming requests (" . count($aim->getInvites()) . ")"));
			$this->addButton(new Button("Outgoing requests (" . count($aim->getOutgoingInvites()) . ")"));
		}
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

		if($response == 0){
			if(!$gang->isLeader($player)){
				$player->showModal(new AllianceMainUi($player, "You must be a gang leader to send alliance requests!"));
				return;
			}
			$player->showModal(new RequestAllianceUi($player));
			return;
		}
		if($response == 1){
			if(count($am->getAlliances($gang)) <= 0){
				$player->showModal(new AllianceMainUi($player, "Your gang does not have any alliances!"));
				return;
			}
			$player->showModal(new ViewAlliancesUi($player));
			return;
		}

		if($response == 2){
			if(count($aim->getInvites()) <= 0){
				$player->showModal(new AllianceMainUi($player, "Your gang does not have any alliance requests!"));
				return;
			}

			$player->showModal(new ViewAllianceRequestsUi($player));
			return;
		}
		if($response == 3){
			if(count($aim->getOutgoingInvites()) <= 0){
				$player->showModal(new AllianceMainUi($player, "Your gang has not sent any alliance requests!"));
				return;
			}

			$player->showModal(new ViewOutgoingAllianceRequestsUi($player));
			return;
		}
	}

}