<?php namespace prison\gangs\uis;

use pocketmine\{
	player\Player,
	Server
};

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\GangInvite;

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class InviteConfirmUi extends ModalWindow{

	public $inviting;

	public function __construct(Player $player, Player $inviting){
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		if($gang == null) return;

		$this->inviting = $inviting->getName();

		parent::__construct("Confirm Invite", "Are you sure you want to invite " . ($n = $inviting->getName()) . " to your gang?", "Invite " . $n, "Go back");
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		if($gang == null){
			$player->sendMessage(TextFormat::RI . "You are no longer in a gang.");
			return;
		}

		if($response){
			$inviting = Server::getInstance()->getPlayerExact($this->inviting);
			if(!$inviting instanceof Player){
				$player->showModal(new InviteMemberUi($player, "This player is no longer online!"));
				return;
			}

			if($gang->inGang($inviting)){
				$player->showModal(new InviteMemberUi($player, "This player is already in a gang!"));
				return;
			}

			$im = $gang->getInviteManager();
			if($im->exists($inviting)){
				$player->showModal(new InviteMemberUi($player, "An invitation has already been sent to this player!"));
				return false;
			}

			/** @var PrisonPlayer $inviting */
			$inv = new GangInvite($gang, $inviting->getUser(), $player->getUser());
			$im->addInvite($inv, true);
			$player->sendMessage(TextFormat::GI . "Sent a gang invite to " . TextFormat::AQUA . $inviting->getName() . TextFormat::GRAY . "! It will expire in " . TextFormat::YELLOW . "60" . TextFormat::GRAY . " seconds");
			return;
		}

		$player->showModal(new InviteMemberUi($player));
	}

}