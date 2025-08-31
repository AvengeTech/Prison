<?php namespace prison\combat\ui\bounty;

use pocketmine\player\Player;

use prison\PrisonPlayer;

use core\ui\elements\simpleForm\Button;
use core\ui\windows\SimpleForm;
use core\utils\TextFormat;

class BountyUi extends SimpleForm{

	public function __construct(Player $player, string $error = "") {
		/** @var PrisonPlayer $player */
		$session = $player->getGameSession()->getCombat();

		parent::__construct("Bounty Menu", ($error == "" ? "" : TextFormat::RED . TextFormat::BOLD . "Error:" . TextFormat::RESET . TextFormat::RED . $error . TextFormat::WHITE . "\n") . "Select an option below!" . ($session->hasBounty() ? "\n\nYou have a bounty worth " . $session->getBountyValue() . " Techits" : ""));

		$this->addButton(new Button("Place new bounty"));
		$this->addButton(new Button("View active bounties"));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if($response == 0){
			$player->showModal(new CreateBountyUi($player));
			return;
		}
		if($response == 1){
			$player->showModal(new ActiveBountiesUi());
		}
	}

}
