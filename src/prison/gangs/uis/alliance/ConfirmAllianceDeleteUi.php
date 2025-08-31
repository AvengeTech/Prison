<?php namespace prison\gangs\uis\alliance;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\Alliance;

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class ConfirmAllianceDeleteUi extends ModalWindow{

	public $ally;

	public function __construct(Player $player, Alliance $ally) {
		/** @var PrisonPlayer $player */
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		if($gang == null) return;

		$this->ally = $ally;

		parent::__construct("Are you sure?", "Are you sure you want to terminate your alliance with " . TextFormat::YELLOW . $ally->getAlly()->getName() . TextFormat::WHITE . "?", "Terminate", "Go back");
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		if($gang == null){
			$player->sendMessage(TextFormat::RI . "You are no longer in a gang!");
			return;
		}

		$ally = $this->ally;
		if(!$ally->verify()){
			$player->showModal(new ViewAlliancesUi($player, "This alliance doesn't exist!"));
			return;
		}

		if($response){
			$ally->terminate();

			$player->sendMessage(TextFormat::GI . "Your gang alliance with " . $ally->getAlly()->getName() . " has been terminated!");
			return;
		}

		$player->showModal(new ViewAllianceUi($player, $ally));
	}

}