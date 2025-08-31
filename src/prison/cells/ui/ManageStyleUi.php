<?php namespace prison\cells\ui;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\Cell;

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Dropdown
};
use core\utils\TextFormat;

class ManageStyleUi extends CustomForm{

	public $cell;

	public $layouts = [];
	public $floors = [];

	public function __construct(Player $player, Cell $cell) {
		/** @var PrisonPlayer $player */
		parent::__construct("Cell " . $cell->getName() . " Style");

		$this->cell = $cell;
		$cells = Prison::getInstance()->getCells();
		$session = $player->getGameSession()->getCells();

		$this->layouts = array_merge(["Do not change"], $session->getLayouts());
		$layouts = array_merge(["Do not change"], $session->getFormattedLayouts());
		$this->addElement(new Label("Select a Cell Layout (" . (count($layouts) - 1) . "/" . count($cells->getLayoutManager()->getLayouts()) . ")"));
		$this->addElement(new Dropdown("Layouts", $layouts));

		$this->floors = array_merge(["Do not change"], $session->getFloors());
		$floors = array_merge(["Do not change"], $session->getFormattedFloors());
		$this->addElement(new Label("Select a Cell Floor"));
		$this->addElement(new Dropdown("Floors", $floors));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$cell = ($cc = Prison::getInstance()->getCells())->getCellManager()->getCellByCell($this->cell);
		$lm = $cc->getLayoutManager();
		if(!$cell->isOwner($player) && !$player->isTier3()){
			$player->sendMessage(TextFormat::RI . "Only cell owners can manage styles!");
			return;
		}
		$session = $player->getGameSession()->getCells();

		if($response[3] !== 0){
			$f = $this->floors[$response[3]] ?? "none";
			if(!$session->hasFloor($f)){
				$player->sendMessage(TextFormat::RI . "You do not have access to floor selected!");
			}else{
				$floor = Prison::getInstance()->getCells()->getLayoutManager()->getFloor($f);
				if($floor == null){
					$session->removeFloor($f);
					$player->sendMessage(TextFormat::RI . "The floor you selected is no longer available!");
				}else{
					$floor->apply($cell);
					$player->sendMessage(TextFormat::GI . "Successfully applied " . TextFormat::YELLOW . $floor->getFormattedName() . TextFormat::GRAY . " floor to your cell!");
				}
				$lm->setCooldown($player);
			}
		}
		if($response[1] !== 0){
			$l = $this->layouts[$response[1]] ?? "none";
			if(!$session->hasLayout($l)){
				$player->sendMessage(TextFormat::RI . "You do not have access to layout selected!");
			}else{
				$layout = Prison::getInstance()->getCells()->getLayoutManager()->getLayout($l);
				if($layout == null){
					$session->removeLayout($l);
					$player->sendMessage(TextFormat::RI . "The layout you selected is no longer available!");
				}else{
					$layout->apply($cell);
					$player->sendMessage(TextFormat::GI . "Successfully applied " . TextFormat::AQUA . $layout->getFormattedName() . TextFormat::GRAY . " layout to your cell!");
				}
				$lm->setCooldown($player);
			}
		}
	}

}