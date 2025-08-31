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

class CombineBooksUI extends CustomForm{

	/** @var RedeemedBook[] $books */
	private array $books = [];
	/** @var EssenceOfKnowledge[] $essences */
	private array $essences = [];

	public function __construct(AtPlayer $player, ?string $message = null) {
		parent::__construct("Combine Books");

		if (!is_null($message)) {
			$this->addElement(new Label(TF::RED . $message . "\n\n" . TF::RESET . TF::WHITE . "Which essence would you like to use?"));
		} else {
			$this->addElement(new Label("Which essence would you like to use?"));
		}

		$dropdown = new Dropdown("Essence Selection");
		$key = 0;
		foreach ($player->getInventory()->getContents(true) as $slot => $item) {
			if($item instanceof EssenceOfKnowledge && !$item->isRaw()){
				$this->essences[$key] = [$slot, $item];
				$msgStart = ($item->getCount() > 1 ? TF::WHITE . $item->getCount() . "x " : "");
				$dropdown->addOption($msgStart . $item->getName() . TF::RESET . TF::YELLOW . " (XP Levels: " . $item->getCost() . ')');
				$key++;
			}
		}
		$this->addElement($dropdown);

		$this->addElement(new Label("Which book would you like to combine?"));

		$dropdown = new Dropdown("Book Selection");
		$key = 0;
		foreach ($player->getInventory()->getContents(true) as $slot => $item) {
			if($item instanceof RedeemedBook && $item->getEnchant()->getStoredLevel() !== $item->getEnchant()->getMaxLevel()){
				$this->books[$key] = [$slot, $item];
				$dropdown->addOption($item->getEnchant()->getLore($item->getEnchant()->getStoredLevel()) . " " . TF::GRAY . "(" . TF::GREEN . $item->getApplyChance() . "%%" . TF::GRAY . ")");
				$key++;
			}
		}
		$this->addElement($dropdown);
	}

	public function handle($response, AtPlayer $player){
		/** @var PrisonPlayer $player */
		if(empty($this->essences) || empty($this->books)) return;

		$essence = $this->essences[$response[1]][1];
		$essenceSlot = $this->essences[$response[1]][0];
		if (!$player->getInventory()->getItem($essenceSlot)->equals($essence, true, true)) {
			$player->sendMessage(TF::RI . "This essence is no longer in your inventory!");
			return;
		}

		$book = $this->books[$response[3]][1];
		$firstBookSlot = $this->books[$response[3]][0];
		if (!$player->getInventory()->getItem($firstBookSlot)->equals($book, true, true)) {
			$player->sendMessage(TF::RI . "This book is no longer in your inventory!");
			return;
		}

		$secondbook = null;

		foreach ($player->getInventory()->getContents(true) as $slot => $item) {
			if ($slot === $firstBookSlot) continue;

			if(
				$item instanceof RedeemedBook && 
				$item->getEnchant()->getRuntimeId() === $book->getEnchant()->getRuntimeId() &&
				$item->getEnchant()->getStoredLevel() === $book->getEnchant()->getStoredLevel()
			){
				$secondbook = $item;
				break;
			}
		}

		if (is_null($secondbook)) {
			$player->showModal(new self($player, "You must have two of the same books to combine!"));
			return;
		}
	
		if($essence->getCost() > $player->getGameSession()->getEssence()->getEssence()){
			$player->sendMessage(TF::RI . "You don't have enough essence to use this essence of knowledge!");
			return;
		}

		$player->showModal(new CombineSelectSecondUI($player, $essence, $book));
	}
}