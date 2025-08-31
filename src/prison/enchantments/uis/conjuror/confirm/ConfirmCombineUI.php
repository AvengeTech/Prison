<?php

namespace prison\enchantments\uis\conjuror\confirm;

use core\AtPlayer;
use core\ui\windows\ModalWindow;
use core\utils\ItemRegistry;
use core\utils\TextFormat as TF;
use prison\enchantments\book\RedeemedBook;
use prison\enchantments\uis\conjuror\CombineBooksUI;
use prison\item\EssenceOfKnowledge;
use prison\PrisonPlayer;

class ConfirmCombineUI extends ModalWindow{

	public function __construct(
		private EssenceOfKnowledge $essence,
		private RedeemedBook $first,
		private RedeemedBook $second
	){
		$content = "Are you sure you want to combine " . $first->getEnchant()->getLore($first->getEnchant()->getStoredLevel()) . TF::WHITE . " (" . TF::GREEN . $first->getApplyChance() . TF::WHITE . ") and " . $second->getEnchant()->getLore($second->getEnchant()->getStoredLevel()) . TF::WHITE . " (" . TF::GREEN . $second->getApplyChance() . TF::WHITE . ") to get " . $first->getEnchant()->getLore($first->getEnchant()->getStoredLevel() + 1) . TF::WHITE . " (" . TF::GREEN . max($first->getApplyChance(), $second->getApplyChance()) . TF::WHITE . ")?" . "\n\n";
		$content .= "Combining these books will cost " . TF::DARK_AQUA . $essence->getCost() . " Essence";

		parent::__construct(
			"Confirm Combine", 
			$content,
			"Combine Books",
			"Go Back"
		);
	}

	public function handle($response, AtPlayer $player){
		/** @var PrisonPlayer $player */
		if($response){
			$essenceSlot = $player->getInventory()->first($this->essence, true);
			if($essenceSlot == -1){
				$player->sendMessage(TF::RN . "The essence you are trying to use is no longer in your inventory!");
				return;
			}

			if($player->getGameSession()->getEssence()->getEssence() < $this->essence->getCost()){
				$player->sendMessage(TF::RN . "You do not have enough Essence to use this!");
				return;
			}


			$firstBookSlot = $player->getInventory()->first($this->first, true);
			if($firstBookSlot === -1){
				$player->sendMessage(TF::RI . "This book is no longer in your inventory!");
				return;
			}

			/** @var RedeemedBook $secondBook */
			$secondBookSlot = $player->getInventory()->first($this->second, true);
			if ($secondBookSlot === -1) {
				$player->sendMessage(TF::RI . "This book is no longer in your inventory!");
				return;
			}

			$enchantment = $this->first->getEnchant()->setStoredLevel($this->first->getEnchant()->getStoredLevel() + 1);

			$newBook = ItemRegistry::REDEEMED_BOOK()->setup(
				$enchantment,
				min($this->first->getApplyCost(), $this->second->getApplyCost()),
				max($this->first->getApplyChance(), $this->second->getApplyChance()),
				false
			);

			$this->essence->pop();

			$player->getGameSession()->getEssence()->subEssence($this->essence->getCost());
			$player->getInventory()->setItem($essenceSlot, $this->essence);
			$player->getInventory()->clear($firstBookSlot);
			$player->getInventory()->clear($secondBookSlot);
			$player->getInventory()->addItem($newBook);

			$player->sendMessage(TF::GI . "You have combined two books and received " . $newBook->getCustomName() . TF::GRAY . "!");
		}else{
			$player->showModal(new CombineBooksUI($player));
		}
	}
}