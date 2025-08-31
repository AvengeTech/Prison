<?php

namespace prison\enchantments\uis\conjuror;

use core\AtPlayer;
use core\ui\elements\customForm\Dropdown;
use core\ui\elements\customForm\Label;
use core\ui\windows\CustomForm;
use core\utils\ItemRegistry;
use core\utils\TextFormat as TF;
use pocketmine\item\Durable;
use prison\enchantments\ItemData;
use prison\enchantments\type\Enchantment;
use prison\enchantments\uis\conjuror\confirm\ConfirmProgressUI;
use prison\item\EssenceOfAscension;
use prison\item\EssenceOfProgress;
use prison\PrisonPlayer;

class AscendEnchantmentUI extends CustomForm{

	/** @var Durable[] $items */
	private array $items = [];
	/** @var EssenceOfAscension[] $essences */
	private array $essences = [];

	public function __construct(AtPlayer $player, string $label = ""){
		parent::__construct("Ascend Enchantment");

		$this->addElement(new Label($label . "Which item's enchantments would you like to ascend?"));

		$dropdown = new Dropdown("Item Selection");
		$key = 0;
		foreach($player->getInventory()->getContents() as $item){
			if($item instanceof Durable && $item->hasEnchantments()){
				$this->items[$key] = $item;
				$dropdown->addOption($item->getName() . TF::RESET . TF::WHITE . " (" . count($item->getEnchantments()) . " enchantments)");
				$key++;
			}
		}
		$this->addElement($dropdown);

		$this->addElement(new Label("Which essence would you like to use?"));
		$dropdown = new Dropdown("Essence Selection");
		$key = 0;
		foreach($player->getInventory()->getContents() as $item){
			if($item instanceof EssenceOfAscension && !$item->isRaw()){
				$this->essences[$key] = $item;
				$msgStart = ($item->getCount() > 1 ? TF::WHITE . $item->getCount() . "x " : "");
				$dropdown->addOption($msgStart . $item->getName() . TF::RESET . TF::GRAY . "(" . TF::AQUA . "Rarity: " . TF::BOLD . substr($item->getRarityName(), 0, 4) . TF::RESET . TF::GRAY . ")");
				$key++;
			}
		}
		$this->addElement($dropdown);
	}

	public function handle($response, AtPlayer $player){
		/** @var PrisonPlayer $player */
		if(empty($this->items) || empty($this->essences)) return;

		$item = $this->items[$response[1]];
		$itemSlot = $player->getInventory()->first($item, true);
		if($itemSlot === -1){
			$player->sendMessage(TF::RI . "This item is no longer in your inventory!");
			return;
		}
		
		$essence = $this->essences[$response[3]];
		$essenceSlot = $player->getInventory()->first($essence, true);
		if($essenceSlot === -1){
			$player->sendMessage(TF::RI . "This essence is no longer in your inventory!");
			return;
		}

		$enchantmentsCount = 0;
		$itemData = new ItemData($item);

		/** @var Enchantment $enchant */
		foreach($itemData->getEnchantments() as $enchant){
			if($enchant->getRarity() === $essence->getRarity()) $enchantmentsCount++;
		}

		if($enchantmentsCount < 1){
			$player->sendMessage(TF::RI . "This item does not have any enchantments that match the essence rarity.");
			return;
		}
	
		if($player->getGameSession()->getEssence()->getEssence() < $essence->getCost()){
			$player->sendMessage(TF::RI . "You need have enough essence to ascend this item's enchantments!");
			return;
		}

		$player->showModal(new SelectEnchantmentUI($item, $essence));
	}
}