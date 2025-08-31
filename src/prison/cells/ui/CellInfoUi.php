<?php namespace prison\cells\ui;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\Cell;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class CellInfoUi extends SimpleForm{

	public $cell;

	public function __construct(Player $player, Cell $cell, string $message = "", bool $error = true) {
		/** @var PrisonPlayer $player */
		$this->cell = $cell;

		if(($t = count(($hm = $cell->getHolderManager())->getHolders())) > 0){
			$claimed = true;
		}else{
			$claimed = false;
		}
		$owner = $hm->getOwner();
		$holder = $hm->getHolderBy($player);

		parent::__construct("Cell Info: " . $cell->getName(), ($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") . ($claimed ?
			"Claimed: " . TextFormat::GREEN . "YES" . TextFormat::WHITE . PHP_EOL . PHP_EOL .

			"Cell owner: " . TextFormat::YELLOW . $owner->getName() . TextFormat::WHITE . PHP_EOL .
			"Expiration date: " . TextFormat::YELLOW . date("m/d/Y", $owner->getExpiration()) . TextFormat::WHITE . PHP_EOL . PHP_EOL .

			"Total stores setup: " . TextFormat::YELLOW . count($owner->getStoreManager()->getStores()) . TextFormat::WHITE . PHP_EOL .

			"Queue: " . PHP_EOL . $hm->getQueueText()
		:
			"Claimed: " . TextFormat::RED . "NO" . TextFormat::WHITE . PHP_EOL . PHP_EOL .

			"This cell is OPEN!"
		));

		$this->addButton(new Button("Goto cell"));

		if(!$claimed){
			$this->addButton(new Button("Purchase cell for 7 days" . PHP_EOL . number_format($cell->getStartingRent()) . " techits"));
		}elseif($claimed && $holder == null){
			$this->addButton(new Button("Queue Cell"));
		}elseif($holder->getXuid() == $owner->getXuid()){
			$this->addButton(new Button("Manage Cell"));
		}else{
			$this->addButton(new Button("In Queue! " . $holder->getExpirationFormatted(true) . PHP_EOL . TextFormat::ITALIC . "Tap to cancel!"));
		}
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$cell = Prison::getInstance()->getCells()->getCellManager()->getCellByCell($this->cell);
		if(($t = count(($hm = $cell->getHolderManager())->getHolders())) > 0){
			$claimed = true;
		}else{
			$claimed = false;
		}

		$owner = $hm->getOwner();
		$holder = $hm->getHolderBy($player);

		if($response == 0){
			$cell->gotoFront($player);
			return;
		}

		if($claimed){
			if($response == 1){
				if(!$hm->isOwner($player)){
					if($holder !== null){
						$player->showModal(new ConfirmQueueLeaveUi($player, $cell));
						//$player->sendMessage(TextFormat::RI . "You are already queued for " . TextFormat::YELLOW . "Cell " . $cell->getName() . ". " . TextFormat::GRAY . "You will gain access on " . TextFormat::YELLOW . $holder->getExpirationFormatted(true));
						return;
					}
					$cells = Prison::getInstance()->getCells()->getCellManager()->getPlayerCells($player, false);
					if(count($cells) >= 1){
						$c = array_shift($cells);
						if($c->isOwner($player)){
							$player->sendMessage(TextFormat::RI . "You are already renting out " . TextFormat::YELLOW . "Cell " . $c->getName());
						}else{
							$holder = $c->getHolderManager()->getHolderBy($player);
							$player->sendMessage(TextFormat::RI . "You are already queued for " . TextFormat::YELLOW . "Cell " . $c->getName() . ". " . TextFormat::GRAY . "You will gain access on " . TextFormat::YELLOW . $holder->getExpirationFormatted(true));
						}
						return;
					}
					if($player->getTechits() < ($r = $cell->getStartingRent())){
						$player->sendMessage(TextFormat::RI . "You need at least " . TextFormat::AQUA . $r . " techits " . TextFormat::GRAY . "to pay for this cell!");
						return;
					}
					$player->showModal(new RentCellUi($player, $cell));
					return;
				}
				$player->showModal(new ManageCellUi($player, $cell));
				return;
			}
		}else{
			if($response == 1){
				$cells = Prison::getInstance()->getCells()->getCellManager()->getPlayerCells($player);
				if(count($cells) >= 1){
					$c = array_shift($cells);
					$holder = $c->getHolderManager()->getHolderBy($player);
					if($c->isOwner($player)){
						$player->sendMessage(TextFormat::RI . "You are already renting out " . TextFormat::YELLOW . "Cell " . $c->getName());
					}else{
						$player->sendMessage(TextFormat::RI . "You are already queued for " . TextFormat::YELLOW . "Cell " . $c->getName() . ". " . TextFormat::GRAY . "You will gain access on " . TextFormat::YELLOW . $holder->getExpirationFormatted(true));
					}
					return;
				}
				if($player->getTechits() < ($r = $cell->getStartingRent())){
					$player->sendMessage(TextFormat::RI . "You need at least " . TextFormat::AQUA . $r . " techits " . TextFormat::GRAY . "to pay for this cell!");
					return; 
				}
				$player->showModal(new RentCellUi($player, $cell));
				return;
			}
		}
	}

}