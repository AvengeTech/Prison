<?php namespace prison\gangs\uis;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class InvitesManagerUi extends SimpleForm{

	public array $invites = [];

	public function __construct(Player $player, string $error = ""){
		parent::__construct("Manage Gang Invites", ($error == "" ? "" : TextFormat::RED . "Error: " . $error . TextFormat::WHITE . PHP_EOL) . "Tap an invite below to see more details!");
		$invites = $this->invites = Prison::getInstance()->getGangs()->getGangManager()->getPlayerInvites($player);
		foreach($invites as $invite){
			$this->addButton(new Button($invite->getGang()->getName() . PHP_EOL . "Invited by: " . $invite->getFrom()->getGamertag()));
		}
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$invite = $this->invites[$response] ?? null;
		if($invite == null){
			$player->sendMessage("idk..");
			return;
		}
		$vi = Prison::getInstance()->getGangs()->getGangManager()->getInviteBy($invite);
		if($vi == null){
			$player->showModal(new InvitesManagerUi($player, TextFormat::RI . "This gang invitation has expired!"));
			return;
		}
		$player->showModal(new ViewInviteUi($player, $vi));
	}
}