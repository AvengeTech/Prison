<?php

namespace prison\enchantments\uis\conjuror\confirm;

use core\AtPlayer;
use core\ui\windows\ModalWindow;
use core\utils\ItemRegistry;
use core\utils\TextFormat as TF;
use prison\enchantments\book\RedeemedBook;
use prison\enchantments\type\Enchantment;
use prison\enchantments\uis\conjuror\SelectRerollUI;
use prison\item\EssenceOfKnowledge;
use prison\PrisonPlayer;

class ConfirmRerollUI extends ModalWindow{

	const OPTION_ENCHANTMENT = 0;
	const OPTION_DOWNGRADE = 1;

	public function __construct(
		private RedeemedBook $book,
		private int $option,
		private ?Enchantment $enchantment = null
	){
		$content = "Are you sure you would like to reroll this book to " . ($option == self::OPTION_DOWNGRADE ? "lower enchantment level by one" : $enchantment->getLore($enchantment->getStoredLevel())) . TF::WHITE . "?" . PHP_EOL . PHP_EOL;
		$content .= "Rerolling this book will cost " . TF::DARK_AQUA . "60 Essence";

		parent::__construct("Confirm Reroll", $content, "Reroll", "Back");
	}
	
	public function handle($response, AtPlayer $player){
		/** @var PrisonPlayer $player */
		if($response){
			if(($bookSlot = $player->getInventory()->first($this->book, true)) == -1){
				$player->sendMessage(TF::RI . "You no longer have the book in your inventory!");
				return;
			}

			$eok = null;
			$essenceSlot = -1;

			foreach($player->getInventory()->getContents() as $index => $item){
				/** @var EssenceOfKnowledge $item */
				if($item->equals(ItemRegistry::ESSENCE_OF_KNOWLEDGE(), false, false) && !$item->isRaw()){
					$eok = $item;
					$essenceSlot = $index;
					break;
				}
			}

			if(is_null($eok)){
				$player->sendMessage(TF::RI . "Your inventory must contain " . TF::AQUA . "Essence of Knowledge" . TF::GRAY . " to do this!");
				return;
			}
	
			if($player->getGameSession()->getEssence()->getEssence() < 60){
				$player->sendMessage(TF::RI . "You don't have enough essence to reroll this book!");
				return;
			}

			if($this->option === self::OPTION_DOWNGRADE){
				$this->book->setup(
					$this->book->getEnchant()->setStoredLevel($this->book->getEnchant()->getStoredLevel() - 1),
					$this->book->getApplyCost(),
					$this->book->getApplyChance(),
					$this->book->canSkipTiers(),
					true,
					[]
				);
			}else{
				$this->book->setup(
					$this->enchantment,
					$this->book->getApplyCost(),
					$this->book->getApplyChance(),
					$this->book->canSkipTiers(),
					true,
					[]
				);
			}

			$eok->pop();

			$player->getGameSession()->getEssence()->subEssence(60);
			$player->getInventory()->setItem($essenceSlot, $eok);
			$player->getInventory()->setItem($bookSlot, $this->book);

			$player->sendMessage(TF::GI . "Rerolled your book to " . $this->book->getEnchant()->getLore($this->book->getEnchant()->getStoredLevel())) . TF::GRAY . "!";
		}else{
			$player->showModal(new SelectRerollUI($this->book));
		}
	}
}