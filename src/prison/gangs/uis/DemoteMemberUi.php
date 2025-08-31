<?php namespace prison\gangs\uis;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\{
	GangMember,
	MemberManager
};

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Dropdown,
};
use core\utils\TextFormat;

class DemoteMemberUi extends CustomForm{

	public $members = [];

	public function __construct(Player $player, string $error = ""){
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		if($gang == null) return;

		$mm = $gang->getMemberManager();
		$member = $mm->getMember($player);
		$members = $this->members = array_values($mm->getMembersBelow($member->getRole() == GangMember::ROLE_LEADER ? GangMember::ROLE_CO_LEADER : GangMember::ROLE_ELDER));
		foreach($members as $k => $m){
			if($m->getRole() == GangMember::ROLE_MEMBER) unset($members[$k]);
		}

		parent::__construct("Demote Gang Member");
		$this->addElement(new Label(($error == "" ? "" : TextFormat::RED . "Error: " . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL) . ""));

		$dd = new Dropdown("Select which member you would like to demote?", ["Go back"]);
		foreach($members as $member){
			$dd->addOption($member->getName() . " (" . $mm->getRoleName($member->getRole()) . ")");
		}
		$this->addElement($dd);
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		if($gang == null){
			$player->sendMessage(TextFormat::RI . "You are no longer in a gang!");
			return;
		}
		$mm = $gang->getMemberManager();

		$member = $response[1] - 1;
		if($member == -1) return;

		$member = $this->members[$member];

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

		$player->showModal(new DemoteConfirmUi($player, $member));
	}

}