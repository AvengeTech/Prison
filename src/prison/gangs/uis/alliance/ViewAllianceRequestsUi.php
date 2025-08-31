<?php namespace prison\gangs\uis\alliance;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\Gang;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class ViewAllianceRequestsUi extends SimpleForm{

	public $invites = [];

	public function __construct(Player $player, $error = "") {
		/** @var PrisonPlayer $player */
		parent::__construct("Incoming Alliance Requests", ($error == "" ? "" : TextFormat::RED . "Error: " . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL) . "Select a request below for more information!");

		$gm = Prison::getInstance()->getGangs()->getGangManager();
		$gang = $gm->getPlayerGang($player);
		$aim = $gang->getAllianceInviteManager();

		foreach($aim->getInvites() as $invite){
			$this->invites[] = $invite;
			$ally = $invite->getAlly();
			$this->addButton(new Button($ally->getName() . " - " . count($ally->getMemberManager()->getMembers()) . "/" . $ally->getMaxMembers()));
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

		foreach($this->invites as $key => $invite){
			if($response == $key){
				if(!$invite->verify()){
					$player->showModal(new ViewAllianceRequestsUi($player, "This request no longer exists!"));
					return;
				}

				$player->showModal(new IncomingAllianceRequestUi($player, $invite));
				return;
			}
		}

		$player->showModal(new AllianceMainUi($player));
	}

}