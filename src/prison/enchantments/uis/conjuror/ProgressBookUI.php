<?php

namespace prison\enchantments\uis\conjuror;

use core\AtPlayer;
use core\ui\elements\customForm\Dropdown;
use core\ui\elements\customForm\Label;
use core\ui\windows\CustomForm;
use core\utils\ItemRegistry;
use core\utils\TextFormat as TF;
use prison\enchantments\book\RedeemedBook;
use prison\enchantments\uis\conjuror\confirm\ConfirmProgressUI;
use prison\item\EssenceOfProgress;
use prison\PrisonPlayer;

class ProgressBookUI extends CustomForm{

	/** @var RedeemedBook[] $books */
	private array $books = [];

	public function __construct(AtPlayer $player){
		parent::__construct("Progress Book");

		$this->addElement(new Label("Which book would you like to reroll?"));

		$dropdown = new Dropdown("Book Selection");
		$key = 0;
		foreach($player->getInventory()->getContents() as $item){
			if($item instanceof RedeemedBook && !$item->canSkipTiers()){
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
	
		$eop = null;

		foreach($player->getInventory()->getContents() as $item){
			/** @var EssenceOfProgress $item */
			if($item->equals(ItemRegistry::ESSENCE_OF_PROGRESS(), false, false) && !$item->isRaw()){
				$eop = $item;
				break;
			}
		}

		if(is_null($eop)){
			$player->sendMessage(TF::RI . "Your inventory must contain " . TF::DARK_PURPLE . "Essence of Progress" . TF::GRAY . " to do this!");
			return;
		}
		
		$book = $this->books[$response[1]];
		$bookSlot = $player->getInventory()->first($book, true);
		if($bookSlot === -1){
			$player->sendMessage(TF::RI . "This book is no longer in your inventory!");
			return;
		}
	
		if($player->getGameSession()->getEssence()->getEssence() < 100){
			$player->sendMessage(TF::RI . "You need " . TF::DARK_AQUA . "100 essence" . TF::GRAY . " to progress this book!");
			return;
		}

		$player->showModal(new ConfirmProgressUI($book));
	}
}