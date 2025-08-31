<?php namespace prison\gangs\uis;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Input
};
use core\utils\TextFormat;

class GangDescriptionUi extends CustomForm{

	public $menu;

	public function __construct(Player $player, bool $menu = false){
		$this->menu = $menu;
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		if($gang == null) return;

		parent::__construct("Set Gang Description");
		$this->addElement(new Label("Edit your gang's description by typing in the box below!"));
		$this->addElement(new Input("Description", "Join my gang!", $gang->getDescription()));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		if($gang == null){
			$player->sendMessage(TextFormat::RI . "You are no longer in a gang!");
			return;
		}

		$description = $response[1];
		$gang->setDescription($description);
		if($this->menu){
			$player->showModal(new GangInfoUi($player, $gang, "Successfully updated your gang's description!", false));
		}else{
			$player->sendMessage(TextFormat::GI . "Successfully updated your gang's description!");
		}
	}

}