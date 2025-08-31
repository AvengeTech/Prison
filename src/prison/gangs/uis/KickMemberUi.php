<?php namespace prison\gangs\uis;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Dropdown,
	Input
};
use core\utils\TextFormat;

class KickMemberUi extends CustomForm{

	public $members = [];

	public function __construct(Player $player, string $error = ""){
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		if($gang == null) return;

		$mm = $gang->getMemberManager();
		$members = $this->members = array_values($mm->getMembersBelow($gang->getRole($player) - 1));

		parent::__construct("Kick Gang Member");
		$this->addElement(new Label(($error == "" ? "" : TextFormat::RED . "Error: " . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL) . ""));

		$dd = new Dropdown("Select which member you would like to kick", ["Go back"]);
		foreach($members as $member){
			$dd->addOption($member->getName());
		}
		$this->addElement($dd);

		$this->addElement(new Input("Reason (optional):", "..."));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		if($gang == null){
			$player->sendMessage(TextFormat::RI . "You are no longer in a gang!");
			return;
		}

		$member = $response[1] - 1;
		if($member == -1) return;

		$member = $this->members[$member];

		if(!$gang->inGang($member)){
			$player->sendMessage(TextFormat::RI . "This player is not in your gang anymore!");
			return;
		}

		$player->showModal(new KickConfirmUi($player, $member, $response[2]));
	}

}