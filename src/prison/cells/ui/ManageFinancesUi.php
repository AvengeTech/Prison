<?php namespace prison\cells\ui;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\Cell;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class ManageFinancesUi extends SimpleForm{

	public $cell;

	public function __construct(Player $player, Cell $cell, string $message = "", bool $error = true) {
		/** @var PrisonPlayer $player */
		$this->cell = $cell;
		$holder = $cell->getHolderBy($player);

		$this->addButton(new Button("Deposit techits"));
		$this->addButton(new Button("Withdraw techits"));

		$this->addButton(new Button("Go back"));

		parent::__construct("Manage Cell " . $cell->getName() . " Finances",
			($message !== "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . PHP_EOL . PHP_EOL . TextFormat::WHITE : "") .
			"You have a cell deposit worth " . TextFormat::AQUA . number_format($holder->getDeposit()) . " techits" . TextFormat::WHITE . PHP_EOL . PHP_EOL .
			"Tap an option below to manage your cell finances!"
		);
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$cell = Prison::getInstance()->getCells()->getCellManager()->getCellByCell($this->cell);
		if(!$cell->isOwner($player)){
			$player->sendMessage(TextFormat::RI . "You no longer have access to this cell.");
			return;
		}
		$holder = $cell->getHolderBy($player);

		if($response == 0){
			if($player->getTechits() <= 0){
				$player->showModal(new ManageFinancesUi($player, $cell, "You have no techits to deposit!"));
				return;
			}
			$player->showModal(new CellDepositUi($player, $cell));
			return;
		}

		if($response == 1){
			if($holder->getDeposit() <= 0){
				$player->showModal(new ManageFinancesUi($player, $cell, "You have no techits in this cell to withdraw!"));
				return;
			}
			$player->showModal(new CellWithdrawUi($player, $cell));
			return;
		}

		if($response == 2){
			$player->showModal(new ManageCellUi($player, $cell));
			return;
		}
	}

}