<?php namespace prison\gangs\uis\alliance;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\Gang;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class ViewAlliancesUi extends SimpleForm{

	public $alliances = [];

	public function __construct(Player $player, $error = "") {
		/** @var PrisonPlayer $player */
		parent::__construct("Your alliances", ($error == "" ? "" : TextFormat::RED . "Error: " . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL) . "Select an option below for more information!");

		$gm = Prison::getInstance()->getGangs()->getGangManager();
		$am = $gm->getAllianceManager();
		$gang = $gm->getPlayerGang($player);

		if($gang != null){
			foreach($am->getAlliances($gang) as $ally){
				$ag = $ally->getAlly();
				$this->addButton(new Button($ag->getName() . PHP_EOL . (($c = count($ag->getMemberManager()->getOnlineMembers()))  <= 0 ? TextFormat::RED . "OFFLINE" : TextFormat::GREEN . "ONLINE " . TextFormat::DARK_GRAY . "(" . $c . "/" . $ally->getAlly()->getMaxMembers() . ")")));
				$this->alliances[] = $ally;
			}
		}
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gm = Prison::getInstance()->getGangs()->getGangManager();
		$am = $gm->getAllianceManager();
		$gang = $gm->getPlayerGang($player);

		if($gang == null){
			$player->sendMessage(TextFormat::RI . "You are not in a gang!");
			return;
		}

		foreach($this->alliances as $key => $ally){
			if($response == $key){
				if(!$ally->verify()){
					$player->showModal(new ViewAlliancesUi($player, "This alliance no longer exists."));
					return;
				}
				$player->showModal(new ViewAllianceUi($player, $ally));
				return;
			}
		}
	}

}