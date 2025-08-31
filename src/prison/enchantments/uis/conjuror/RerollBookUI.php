<?php

namespace prison\enchantments\uis\conjuror;

use core\AtPlayer;
use core\ui\elements\customForm\Dropdown;
use core\ui\elements\customForm\Label;
use core\ui\windows\CustomForm;
use core\utils\ItemRegistry;
use core\utils\TextFormat as TF;
use prison\enchantments\book\RedeemedBook;
use prison\item\EssenceOfKnowledge;
use prison\PrisonPlayer;

class RerollBookUI extends CustomForm{

	/** @var RedeemedBook[] $books */
	private array $books = [];

	public function __construct(AtPlayer $player){
		parent::__construct("Reroll Book");

		$this->addElement(new Label("Which book would you like to reroll?"));

		$dropdown = new Dropdown("Book Selection");
		$key = 0;
		foreach($player->getInventory()->getContents() as $item){
			if($item instanceof RedeemedBook && !$item->hasRerolled()){
				$this->books[$key] = $item;
				$dropdown->addOption($item->getEnchant()->getLore($item->getEnchant()->getStoredLevel()));
				$key++;
			}
		}
		$this->addElement($dropdown);
	}

	public function handle($response, AtPlayer $player){
		/** @var PrisonPlayer $player */
		if(empty($this->books)) return;
	
		$eok = null;

		foreach($player->getInventory()->getContents() as $item){
			/** @var EssenceOfKnowledge $item */
			if($item->equals(ItemRegistry::ESSENCE_OF_KNOWLEDGE(), false, false) && !$item->isRaw()){
				$eok = $item;
				break;
			}
		}

		if(is_null($eok)){
			$player->sendMessage(TF::RI . "Your inventory must contain " . TF::AQUA . "Essence of Knowledge" . TF::GRAY . " to do this!");
			return;
		}
		
		$book = $this->books[$response[1]];
		$bookSlot = $player->getInventory()->first($book, true);
		if($bookSlot === -1){
			$player->sendMessage(TF::RI . "This book is no longer in your inventory!");
			return;
		}
	
		if($player->getGameSession()->getEssence()->getEssence() < 60){
			$player->sendMessage(TF::RI . "You need " . TF::DARK_AQUA . "60 essence" . TF::GRAY . " to reroll this book!");
			return;
		}

		if(empty($book->getRerolledEnchantments())){
			$rerolls = $book->generateReroll();
			$book->setup(
				$book->getEnchant(), 
				$book->getApplyCost(), 
				$book->getApplyChance(),
				$book->canSkipTiers(),
				$book->hasRerolled(),
				$rerolls
			);
			$player->getInventory()->setItem($bookSlot, $book);
		}

		$player->showModal(new SelectRerollUI($book));
	}
}