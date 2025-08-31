<?php namespace prison\gangs\uis;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\GangInvite;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class ViewInviteUi extends SimpleForm{

	public $invite;

	public function __construct(Player $player, GangInvite $invite){
		$this->invite = $invite;

		$fg = $invite->getFrom()->getGamertag();
		$life = $invite::MAX_LIFESPAN - $invite->lifespan;
		$gang = $invite->getGang();

		parent::__construct("View Invite", "Gang name: " . $gang->getName() . PHP_EOL . "Leader: " . $gang->getLeader()->getGamertag() . PHP_EOL . "Description: " . $gang->getDescription() . PHP_EOL . PHP_EOL . "Total members: " . count($gang->getMemberManager()->getMembers()) . PHP_EOL . "Trophies: " . $gang->getTrophies() . PHP_EOL . PHP_EOL . "Invited by: " . $fg . PHP_EOL . "Invite will expire in: " . $life . " seconds" . PHP_EOL . PHP_EOL . "Select an option below!");

		$this->addButton(new Button("Accept"));
		$this->addButton(new Button("Decline"));
		$this->addButton(new Button("Go back"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		if($response == 0 || $response == 1){
			$invite = Prison::getInstance()->getGangs()->getGangManager()->getInviteBy($this->invite);
			if($invite == null){
				$player->sendMessage(TextFormat::RI . "This invite has expired!");
				return;
			}
		}

		if($response == 0){
			$gang = $invite->getGang();
			if(count($gang->getMemberManager()->getMembers()) >= $gang->getMaxMembers() && !$player->isTier3()){
				$player->sendMessage(TextFormat::RI . "This gang is full and can no longer accept invites!");
				return;
			}
			$invite->accept();
			return;
		}
		if($response == 1){
			$invite->decline();
			$player->sendMessage(TextFormat::GI . "Gang invitation from " . TextFormat::YELLOW . $invite->getGang()->getName() . TextFormat::GRAY . "!");
			return;
		}
		$player->showModal(new InvitesManagerUi($player));
	}
}