<?php

namespace prison\enchantments\uis\conjuror\confirm;

use core\AtPlayer;
use core\ui\windows\ModalWindow;
use core\utils\ItemRegistry;
use core\utils\TextFormat as TF;
use prison\enchantments\book\RedeemedBook;
use prison\enchantments\uis\conjuror\ProgressBookUI;
use prison\item\EssenceOfProgress;
use prison\PrisonPlayer;

class ConfirmProgressUI extends ModalWindow{

	public function __construct(
		private RedeemedBook $book
	){
		$content = "Are you sure you would like to progress this " . $book->getEnchant()->getLore($book->getEnchant()->getStoredLevel()) . TF::GRAY . "?\n\n";
		$content .= "Progressing a book has a " . TF::GOLD . "50%%%%%" . TF::GRAY . " chance to happen.\n\n";
		$content .= "This will cost " . TF::DARK_AQUA . "100 Essence" . TF::GRAY . ".";

		parent::__construct("Confirm Progress", $content, "Progress", "Back");
	}

	public function handle($response, AtPlayer $player){
		/** @var PrisonPlayer $player */
		if($response){
			if(($bookSlot = $player->getInventory()->first($this->book, true)) == -1){
				$player->sendMessage(TF::RI . "You no longer have the book in your inventory!");
				return;
			}

			$eop = null;
			$essenceSlot = -1;

			foreach($player->getInventory()->getContents() as $index => $item){
				/** @var EssenceOfProgress $item */
				if($item->equals(ItemRegistry::ESSENCE_OF_PROGRESS(), false, false) && !$item->isRaw()){
					$eop = $item;
					$essenceSlot = $index;
					break;
				}
			}

			if(is_null($eop)){
				$player->sendMessage(TF::RI . "Your inventory must contain " . TF::DARK_PURPLE . "Essence of Progress" . TF::GRAY . " to do this!");
				return;
			}
	
			if($player->getGameSession()->getEssence()->getEssence() < 100){
				$player->sendMessage(TF::RI . "You need " . TF::DARK_AQUA . "100 essence" . TF::GRAY . " to progress this book!");
				return;
			}

			if(mt_rand(1, 100) < 50){
				$this->book->setup(
					$this->book->getEnchant(),
					$this->book->getApplyCost(),
					$this->book->getApplyChance(),
					true,
					$this->book->hasRerolled(),
					$this->book->getRerolledEnchantments()
				);
				
				$player->sendMessage(TF::GI . "Your " . $this->book->getEnchant()->getLore($this->book->getEnchant()->getStoredLevel())) . TF::GRAY . " book is now progressed and can skip the tier system!";
			}else{
				$player->sendMessage(TF::GI . "Your " . $this->book->getEnchant()->getLore($this->book->getEnchant()->getStoredLevel())) . TF::GRAY . " book failed to progress, better luck next time!";
			}

			$eop->pop();

			$player->getGameSession()->getEssence()->subEssence(100);
			$player->getInventory()->setItem($essenceSlot, $eop);
			$player->getInventory()->setItem($bookSlot, $this->book);
		}else{
			$player->showModal(new ProgressBookUI($player));
		}
	}
}