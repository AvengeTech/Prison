<?php namespace prison\gangs\uis;

use pocketmine\{
	player\Player,
	Server
};

use prison\Prison;
use prison\PrisonPlayer;

use core\Core;
use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Dropdown,
};
use core\utils\TextFormat;

class InviteMemberUi extends CustomForm{

	public array $players = [];

	public function __construct(Player $player, string $error = ""){
		$gang = ($gm = Prison::getInstance()->getGangs()->getGangManager())->getPlayerGang($player);
		if($gang == null) return;

		parent::__construct("Invite Gang Member");
		$this->addElement(new Label(($error == "" ? "" : TextFormat::RED . "Error: " . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL) . "Select an online player from the list below to invite!"));

		$dd = new Dropdown("Players", ["Cancel"]);

		foreach(Server::getInstance()->getOnlinePlayers() as $pl){
			if(!$gm->inGang($pl) && !$gang->getInviteManager()->exists($pl)){
				$dd->addOption($pl->getName());
				$this->players[] = $pl->getName();
			}
		}
		$this->addElement($dd);
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gang = ($gm = Prison::getInstance()->getGangs()->getGangManager())->getPlayerGang($player);
		if($gang == null){
			$player->sendMessage(TextFormat::RI . "You are no longer in a gang!");
			return;
		}

		$p = $response[1] - 1;
		if($p == -1) return;

		$p = $this->players[$p];
		$p = Server::getInstance()->getPlayerExact($p);
		if(!$p instanceof Player){
			$player->showModal(new InviteMemberUi($player, "This player is no longer near you!"));
			return;
		}
		if($gm->inGang($p)){
			$player->showModal(new InviteMemberUi($player, "This player has already joined a gang!"));
			return;
		}
		
		$player->showModal(new InviteConfirmUi($player, $p));
	}

}