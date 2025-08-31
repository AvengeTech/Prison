<?php

namespace prison\enchantments\uis\conjuror;

use core\AtPlayer;
use core\ui\elements\customForm\Dropdown;
use core\ui\elements\customForm\Label;
use core\ui\windows\CustomForm;
use core\utils\TextFormat as TF;
use pocketmine\item\Durable;
use prison\enchantments\ItemData;
use prison\enchantments\type\Enchantment;
use prison\enchantments\uis\conjuror\confirm\ConfirmAscendUI;
use prison\item\EssenceOfAscension;
use prison\PrisonPlayer;

class SelectEnchantmentUI extends CustomForm{

	/** @var Enchantment[] $enchantments */
	private array $enchantments = [];

	public function __construct(
		private Durable $item,
		private EssenceOfAscension $essence
	){
		parent::__construct("Select Enchantment");

		$this->addElement(new Label("Which enchantment would you like to ascend?"));

		$dropdown = new Dropdown("Enchantment Selection");
		$key = 0;
		$itemData = new ItemData($item);

		/** @var Enchantment $enchantment */
		foreach($itemData->getEnchantments() as $enchantment){
			if($enchantment->getRarity() === $essence->getRarity() && $enchantment->getStoredLevel() < $enchantment->getMaxLevel()){
				$this->enchantments[$key] = $enchantment;
				$dropdown->addOption($enchantment->getLore($enchantment->getStoredLevel()));
				$key++;
			}
		}
		$this->addElement($dropdown);
	}

	public function handle($response, AtPlayer $player){
		/** @var PrisonPlayer $player */
		if(empty($this->enchantments)){
			$player->showModal(new AscendEnchantmentUI($player, TF::RED . "You do not have any non-max enchantments that match with the essence rarity."));
			return;
		}

		$itemSlot = $player->getInventory()->first($this->item, true);
		if($itemSlot === -1){
			$player->sendMessage(TF::RI . "This item is no longer in your inventory!");
			return;
		}

		$essenceSlot = $player->getInventory()->first($this->essence, true);
		if($essenceSlot === -1){
			$player->sendMessage(TF::RI . "This essence is no longer in your inventory!");
			return;
		}
	
		if($player->getGameSession()->getEssence()->getEssence() < $this->essence->getCost()){
			$player->sendMessage(TF::RI . "You need have enough essence to ascend this item's enchantments!");
			return;
		}

		$enchantment = $this->enchantments[$response[1]];

		if($enchantment->getStoredLevel() >= $enchantment->getMaxLevel()){
			$player->sendMessage(TF::RI . "Could not select that enchantment anymore, it is more than or equal to the max level.");
			return;
		}

		$player->showModal(new ConfirmAscendUI($this->item, $this->essence, $enchantment));
	}
}