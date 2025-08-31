<?php

namespace prison\enchantments\uis\conjuror\confirm;

use core\AtPlayer;
use core\ui\windows\ModalWindow;
use core\utils\TextFormat as TF;
use pocketmine\item\Durable;
use prison\enchantments\ItemData;
use prison\enchantments\type\Enchantment;
use prison\enchantments\uis\conjuror\ProgressBookUI;
use prison\item\EssenceOfAscension;
use prison\PrisonPlayer;

class ConfirmAscendUI extends ModalWindow{

	public function __construct(
		private Durable $item,
		private EssenceOfAscension $essence,
		private Enchantment $enchantment
	){
		$content = "Are you sure you would like to ascend " . $item->getName() . TF::RESET . TF::GRAY . "'s " . $enchantment->getLore($enchantment->getStoredLevel()) . TF::GRAY . " to " . $enchantment->getLore($enchantment->getStoredLevel() + 1) . TF::GRAY . "?\n\n";
		$content .= "This will cost " . TF::DARK_AQUA . $essence->getCost() . " Essence" . TF::GRAY . ".";

		parent::__construct("Confirm Ascend", $content, "Ascend", "Back");
	}

	public function handle($response, AtPlayer $player){
		/** @var PrisonPlayer $player */
		if($response){
			if(($itemSlot = $player->getInventory()->first($this->item, true)) == -1){
				$player->sendMessage(TF::RI . "You no longer have this item in your inventory!");
				return;
			}

			if(($essenceSlot = $player->getInventory()->first($this->essence, true)) == -1){
				$player->sendMessage(TF::RI . "You no longer have this essence in your inventory!");
				return;
			}
	
			if($player->getGameSession()->getEssence()->getEssence() < $this->essence->getCost()){
				$player->sendMessage(TF::RI . "You do not have enough essence to ascend this item's enchantment!");
				return;
			}

			$itemData = new ItemData($this->item);
			$itemData->addEnchantment($this->enchantment, $this->enchantment->getStoredLevel() + 1);

			$this->essence->pop();

			$player->getGameSession()->getEssence()->subEssence($this->essence->getCost());
			$player->getInventory()->setItem($essenceSlot, $this->essence);
			$player->getInventory()->setItem($itemSlot, $itemData->getItem());
		}else{
			$player->showModal(new ProgressBookUI($player));
		}
	}
}