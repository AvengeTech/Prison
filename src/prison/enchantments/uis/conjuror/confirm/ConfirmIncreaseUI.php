<?php

namespace prison\enchantments\uis\conjuror\confirm;

use core\AtPlayer;
use core\ui\windows\ModalWindow;
use core\utils\TextFormat as TF;
use prison\enchantments\book\RedeemedBook;
use prison\enchantments\uis\conjuror\IncreaseChancesUI;
use prison\item\EssenceOfSuccess;
use prison\item\UnboundTome;
use prison\PrisonPlayer;

class ConfirmIncreaseUI extends ModalWindow{

	public function __construct(
		private RedeemedBook|UnboundTome $item, 
		private EssenceOfSuccess $essence
	){
		$itemContent = ($item instanceof RedeemedBook ? $item->getEnchant()->getLore($item->getEnchant()->getStoredLevel()) . TF::WHITE . " book?" : $item->getName() . TF::WHITE . "?");

		parent::__construct(
			"Confirm Increase",
			"Are you sure you want to apply the " . $essence->getName() . TF::RESET . TF::YELLOW . "(Increase: " . $essence->getPercent() . ")" . TF::WHITE . " essence to your " . $itemContent . PHP_EOL . PHP_EOL .
			"This will cost you " . TF::DARK_AQUA . $essence->getCost() . " Essence" . TF::WHITE . " to use.",
			"Apply Essence",
			"Go back"
		);
	}

	public function handle($response, AtPlayer $player){
		/** @var PrisonPlayer $player */
		$islot = $player->getInventory()->first($this->item, true);
		if($islot === -1){
			$player->sendMessage(TF::RI . "This item is no longer in your inventory!");
			return;
		}

		$eslot = $player->getInventory()->first($this->essence, true);
		if($eslot === -1){
			$player->sendMessage(TF::RI . "This essence is no longer in your inventory!");
			return;
		}
		if($this->essence->getCost() > $player->getGameSession()->getEssence()->getEssence()){
			$player->sendMessage(TF::RI . "You don't have enough essence to use this essence of success!");
			return;
		}
		
		if($response){
			if(!$player->getInventory()->canAddItem($this->item)){
				$player->sendMessage(TF::RI . "You must have at least 1 free slot!");
				return;
			}

			$this->item->increaseChance($this->essence->getPercent());
				
			$this->essence->pop();

			$player->getInventory()->setItem($eslot, $this->essence);
			$player->getInventory()->setItem($islot, $this->item);
			$player->getGameSession()->getEssence()->subEssence($this->essence->getCost());

			$player->sendMessage(TF::GI . "Successfully increased chances to your item!");
		}else{
			$player->showModal(new IncreaseChancesUI($player));
		}
	}
}