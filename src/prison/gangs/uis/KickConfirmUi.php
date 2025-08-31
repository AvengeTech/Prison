<?php namespace prison\gangs\uis;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\GangMember;

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class KickConfirmUi extends ModalWindow{

	public $member;
	public $reason;

	public function __construct(Player $player, GangMember $member, string $reason = ""){
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		if($gang == null) return;

		$this->member = $member;
		$this->reason = $reason;

		parent::__construct("Are you sure?", "Are you sure you want to kick " . ($gt = $member->getUser()->getGamertag()) . " from your gang?" . ($reason == "" ? "" : PHP_EOL . PHP_EOL . "Reason: " . $reason), "Kick " . $gt, "Go back");
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		if($gang == null){
			$player->sendMessage(TextFormat::RI . "You are no longer in a gang.");
			return;
		}

		if($response){
			$member = $this->member;
			if(!$gang->inGang($member)){
				$player->sendMessage(TextFormat::RI . "This player is not in your gang anymore!");
				return;
			}
			$gang->getMemberManager()->removeMember($member->getXuid(), true, true);
			if($member->isOnline()){
				$member->getPlayer()->sendMessage(TextFormat::RI . "You are no longer in a gang! Kicked by " . TextFormat::YELLOW . $player->getName() . ($this->reason == "" ? "" : TextFormat::GRAY . " for: " . $this->reason));
			}
			$player->sendMessage(TextFormat::GI . $member->getName() . " has been kicked from your gang!");
			return;
		}

		$player->showModal(new KickMemberUi($player));
	}

}