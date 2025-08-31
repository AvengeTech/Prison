<?php namespace prison\gangs\uis;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\{
	GangMember,
	MemberManager
};

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class DemoteConfirmUi extends ModalWindow{

	public $member;

	public function __construct(Player $player, GangMember $member){
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		if($gang == null) return;
		$mm = $gang->getMemberManager();
		$this->member = $member;
		$role = $member->getRole();

		parent::__construct("Are you sure?", "Are you sure you want to demote " . ($gt = $member->getUser()->getGamertag()) . " from " . $mm->getRoleName($role) . " to " . $mm->getRoleName($mm->getRoleBelow($role)) . "?", "Demote " . $gt, "Go back");
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		$mm = $gang->getMemberManager();
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

			if(
				$member->getRole() == GangMember::ROLE_CO_LEADER &&
				count($mm->getMembers(GangMember::ROLE_ELDER)) >= MemberManager::MAX_ELDERS
			){
				$player->sendMessage(TextFormat::RI . "You can only have a maximum of 2 elders in your gang! Please demote one before demoting co-leader to elder!");
				return;
			}

			$nr = $mm->getRoleBelow($member->getRole());
			if ($nr === -1) {
				$player->showModal(new KickConfirmUi($player, $member, "demoted"));
				return;
			}
			$member->setRole($nr, true);
			if($gang->getMemberManager()->updateMember($member)){
				if($member->isOnline()){
					$member->getPlayer()->sendMessage(TextFormat::RI . "You have been demoted to " . $mm->getRoleName($nr) . " in your gang!");
				}
				$player->sendMessage(TextFormat::GI . $member->getName() . " has been demoted to " . $mm->getRoleName($nr) . "!");
				return;
			}
			$player->sendMessage(TextFormat::RI . "An error occured when trying to demote this player.");
			return;
		}

		$player->showModal(new DemoteMemberUi($player));
	}

}