<?php namespace prison\cells\ui;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\{
	Cell,
	CellHolder
};

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class RentCellUi extends ModalWindow{

	public $cell;

	public function __construct(Player $player, Cell $cell) {
		/** @var PrisonPlayer $player */
		$this->cell = $cell;

		$r = $cell->getStartingRent();
		$owner = $cell->getOwner();
		parent::__construct("Cell " . $cell->getName() . " agreement",
			"This cell has a queue price of " .
				TextFormat::AQUA . number_format($r) . " techits" . TextFormat::WHITE .
			". By purchasing, you are agreeing to pay " .
				TextFormat::AQUA . number_format($r) . " techits " . TextFormat::WHITE .
			"to temporarily use this cell." . PHP_EOL . PHP_EOL .

			($owner !== null ?
				"This cell is currently being used! By selecting queue cell, you will be added to the end of the waiting queue. On " . TextFormat::YELLOW . date("m/d/Y", $cell->getHolderManager()->getLatestQueued()->getExpiration()) . TextFormat::WHITE . ", this cell will be available to you. (NOTE: You can still edit your cell stores while queued by typing /mycell managestores)" :
				"By selecting purchase cell, you will pay for this cell and have access to it until " . TextFormat::YELLOW . date("m/d/Y", $cell->getNewExpiration()) . TextFormat::WHITE . ". Once your time is up, all techits made from your cell stores will automatically be returned to you, and your cell store items will be safe until you claim another cell."
			) . PHP_EOL . PHP_EOL . 
			"Are you sure you would like to " . ($owner !== null ? "queue for" : "purchase") . " this cell?",

			($owner !== null ? "Queue Cell " : "Purchase Cell ") . $cell->getName(),
			"Go back"
		);
	}
	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$cell = Prison::getInstance()->getCells()->getCellManager()->getCellByCell($this->cell);

		if($response){
			if(($t = count(($hm = $cell->getHolderManager())->getHolders())) >= 10){
				$player->sendMessage(TextFormat::RI . "This cell has already been fully queued!");
				return;
			}
			if($player->getTechits() < ($r = $cell->getStartingRent())){
				$player->sendMessage(TextFormat::RI . "You need at least " . TextFormat::AQUA . number_format($r) . " techits " . TextFormat::GRAY . "to pay for this cell!");
				return; 
			}

			if($cell->getOwner() == null){
				$cell->claim($player);
				$player->sendMessage(TextFormat::GI . "You have successfully claimed this cell! To manage it, type " . TextFormat::YELLOW . "/mycell manage");
			}else{
				$player->takeTechits($r);
				$ch = new CellHolder($player->getUser(), $cell, false, $cell->getNewExpiration());
				$ch->setChanged();
				$cell->getHolderManager()->addHolder($ch, true, true);
				$player->sendMessage(TextFormat::GI . "You have successfully queued for this cell! You will receive an inbox when it is available to use!");
				$player->sendMessage(TextFormat::GI . "You can type " . TextFormat::YELLOW . "/mycell managestores" . TextFormat::GRAY . " right now to start editing your stores while in the queue!");
			}
			return;
		}
		$player->showModal(new CellInfoUi($player, $cell));
	}

}
