<?php namespace prison\cells\ui;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\Cell;
use prison\cells\stores\ui\manage\ManageStoresUi;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class ManageCellUi extends SimpleForm{

	public $cell;

	public function __construct(Player $player, Cell $cell, string $message = "", bool $error = true) {
		/** @var PrisonPlayer $player */
		$this->cell = $cell;
		$hm = $cell->getHolderManager();
		$holder = $hm->getHolderBy($player);

		$this->addButton(new Button("Manage finances" . PHP_EOL . "Cell deposit: " . number_format($holder->getDeposit())));
		$this->addButton(new Button("Manage stores" . PHP_EOL . count(($sm = $holder->getStoreManager())->getStores()) . "/" . $sm->getMaxStores($player) . " stores setup"));
		$this->addButton(new Button("Manage style"));

		parent::__construct(
			"Manage Cell " . $cell->getName(),
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"Tap an option below to manage your cell!"
		);
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$cell = Prison::getInstance()->getCells()->getCellManager()->getCellByCell($this->cell);
		if(!$cell->isHolder($player)){
			$player->sendMessage(TextFormat::RI . "You no longer have access to this cell.");
			return;
		}

		if($response == 0){
			$player->showModal(new ManageFinancesUi($player, $cell));
			return;
		}
		if($response == 1){
			$player->showModal(new ManageStoresUi($player, $cell));
			return;
		}

		if($response == 2){
			if(($lm = Prison::getInstance()->getCells()->getLayoutManager())->hasCooldown($player)){
				$player->showModal(new ManageCellUi($player, $cell, "You must wait another " . TextFormat::YELLOW . $lm->getCooldown($player) . " seconds" . TextFormat::RED . " before editing your cell style again!"));
				return;
			}
			$player->showModal(new ManageStyleUi($player, $cell));
			return;
		}

	}

}