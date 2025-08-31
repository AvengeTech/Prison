<?php

namespace prison\enchantments\uis\conjuror;

use core\AtPlayer;
use core\ui\elements\customForm\Dropdown;
use core\ui\elements\customForm\Label;
use core\ui\windows\CustomForm;
use core\utils\TextFormat as TF;
use prison\enchantments\book\RedeemedBook;
use prison\enchantments\uis\conjuror\confirm\ConfirmCombineUI;
use prison\item\EssenceOfKnowledge;
use prison\PrisonPlayer;

class CombineSelectSecondUI extends CustomForm {

	/** @var RedeemedBook[] $books */
	private array $books = [];

	public function __construct(
		AtPlayer $player,
		private EssenceOfKnowledge $essence,
		private RedeemedBook $firstBook,
		?string $message = null
	) {
		parent::__construct("Combine Books");

		if (!is_null($message)) {
			$this->addElement(new Label(TF::RED . $message . "\n\n" . TF::RESET . TF::WHITE . "Which book would you like to combine with " . $firstBook->getEnchant()->getLore($firstBook->getEnchant()->getStoredLevel()) . TF::WHITE . " (" . TF::GREEN . $firstBook->getApplyChance() . "%%" . TF::WHITE . ")?"));
		} else {
			$this->addElement(new Label("Which book would you like to combine with " . $firstBook->getEnchant()->getLore($firstBook->getEnchant()->getStoredLevel()) . TF::WHITE . " (" . TF::GREEN . $firstBook->getApplyChance() . "%%" . TF::WHITE . ")?"));
		}

		$dropdown = new Dropdown("Book Selection");
		$key = 0;
		foreach ($player->getInventory()->getContents(true) as $slot => $item) {
			if ($item->equals($firstBook, true, true)) continue;
			if ($item instanceof RedeemedBook && $item->getEnchant()->getStoredLevel() !== $item->getEnchant()->getMaxLevel()) {
				$this->books[$key] = [$slot, $item];
				$dropdown->addOption($item->getEnchant()->getLore($item->getEnchant()->getStoredLevel()) . TF::RESET . " " . TF::GRAY . "(" . TF::GREEN . $item->getApplyChance() . "%%" . TF::GRAY . ")");
				$key++;
			}
		}
		$this->addElement($dropdown);
	}

	public function handle($response, AtPlayer $player) {
		/** @var PrisonPlayer $player */
		if (empty($this->books)) return;

		$secondbook = $this->books[$response[1]][1];
		$secondBookSlot = $this->books[$response[1]][0];
		if (!$player->getInventory()->getItem($secondBookSlot)->equals($secondbook, true, true)) {
			$player->sendMessage(TF::RI . "This book is no longer in your inventory!");
			return;
		}

		if ($this->essence->getCost() > $player->getGameSession()->getEssence()->getEssence()) {
			$player->sendMessage(TF::RI . "You don't have enough essence to use this essence of knowledge!");
			return;
		}

		$player->showModal(new ConfirmCombineUI($this->essence, $this->firstBook, $secondbook));
	}
}
