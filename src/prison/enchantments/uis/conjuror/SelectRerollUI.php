<?php

namespace prison\enchantments\uis\conjuror;

use core\AtPlayer;
use core\ui\elements\customForm\Dropdown;
use core\ui\windows\CustomForm;
use core\utils\ItemRegistry;
use core\utils\TextFormat as TF;
use prison\enchantments\book\RedeemedBook;
use prison\enchantments\type\Enchantment;
use prison\enchantments\uis\conjuror\confirm\ConfirmRerollUI;
use prison\item\EssenceOfKnowledge;
use prison\PrisonPlayer;

class SelectRerollUI extends CustomForm{

	/** @var Enchantment $rerolls */
	private array $rerolls = [];

	public function __construct(
		private RedeemedBook $book
	){
		parent::__construct("Reroll Book");

		$dropdown = new Dropdown("Reroll Selection");
		$key = 0;
		foreach($book->getRerolledEnchantments() as $enchantment){
			$this->rerolls[$key] = $enchantment;
			$dropdown->addOption($enchantment->getLore($enchantment->getStoredLevel()));
			$key++;
		}
		if($book->getEnchant()->getStoredLevel() !== 1) $dropdown->addOption("Reduce book level by 1");
		$this->addElement($dropdown);
	}

	public function handle($response, AtPlayer $player){
		/** @var PrisonPlayer $player */
		if(empty($this->rerolls)) return;
		
		if($player->getInventory()->first($this->book, true) == -1){
			$player->sendMessage(TF::RI . "You no longer have the book in your inventory!");
			return;
		}
		
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

		if($response[0] < 4){
			$player->showModal(new ConfirmRerollUI($this->book, ConfirmRerollUI::OPTION_ENCHANTMENT, $this->rerolls[$response[0]]));
			return;
		}else{
			$player->showModal(new ConfirmRerollUI($this->book, ConfirmRerollUI::OPTION_DOWNGRADE));
			return;
		}
	}
}