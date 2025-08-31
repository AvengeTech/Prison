<?php namespace prison\cells\ui;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\Cell;

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Slider
};
use core\utils\TextFormat;

class CellWithdrawUi extends CustomForm{

	public $cell;

	public function __construct(Player $player, Cell $cell) {
		/** @var PrisonPlayer $player */
		parent::__construct("Cell " . $cell->getName() . " Withdraw");

		$this->cell = $cell;
		$holder = $cell->getHolderBy($player);

		$this->addElement(new Label(
			"You have " . TextFormat::AQUA . number_format($player->getTechits()) . " techits" . TextFormat::WHITE . PHP_EOL .
			"Your cell deposit is worth " . TextFormat::AQUA . number_format($holder->getDeposit()) . " techits" . TextFormat::WHITE . PHP_EOL . PHP_EOL .

			"How many would you like to withdraw?"
		));
		$this->addElement(new Slider("Techits", 5, $holder->getDeposit(), 5));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$cell = Prison::getInstance()->getCells()->getCellManager()->getCellByCell($this->cell);
		if(!$cell->isHolder($player)){
			$player->sendMessage(TextFormat::RI . "You no longer have access to this cell.");
			return;
		}
		$holder = $cell->getHolderBy($player);

		$techits = $response[1];
		if($techits > $holder->getDeposit()){
			$player->sendMessage(TextFormat::RI . "You do not have enough techits to withdraw!");
			return;
		}

		$holder->takeFromDeposit($techits, $player);
		$player->showModal(new ManageFinancesUi($player, $cell, "Withdrew " . TextFormat::AQUA . number_format($techits) . " techits " . TextFormat::GREEN . "from your cell deposit!", false));
	}

}