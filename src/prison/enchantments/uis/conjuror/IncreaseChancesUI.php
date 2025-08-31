<?php

namespace prison\enchantments\uis\conjuror;

use core\ui\elements\customForm\Dropdown;
use core\ui\elements\customForm\Label;
use core\ui\windows\CustomForm;
use core\utils\TextFormat as TF;
use pocketmine\player\Player;
use prison\enchantments\book\RedeemedBook;
use prison\enchantments\uis\conjuror\confirm\ConfirmIncreaseUI;
use prison\item\EssenceOfSuccess;
use prison\item\UnboundTome;
use prison\PrisonPlayer;

class IncreaseChancesUI extends CustomForm{

	private array $items = [];
	/** @var EssenceOfSuccess[] $essences */
	private array $essences = [];

	public function __construct(Player $player){
		/** @var PrisonPlayer $player */
		parent::__construct("Increase Chances");

		$this->addElement(new Label("Which item would you like to apply essence to?"));

		$dropdown = new Dropdown("Item Selection");
		$key = 0;
		foreach($player->getInventory()->getContents() as $item){
			if(
				$item instanceof RedeemedBook && $item->getApplyChance() !== 100 ||
				$item instanceof UnboundTome && $item->getReturnChance() !== 100
			){
				$this->items[$key] = $item;

				if($item instanceof RedeemedBook){
					$dropdown->addOption($item->getEnchant()->getLore($item->getEnchant()->getStoredLevel()) . TF::RESET . TF::YELLOW . " - Chance: " . $item->getApplyChance() . "%%");
				}else{
					$dropdown->addOption($item->getName() . TF::RESET . TF::YELLOW . " - Chance: " . $item->getReturnChance() . "%%");
				}
				$key++;
			}
		}
		$this->addElement($dropdown);

		$this->addElement(new Label("Which essence would you like to use?"));
		$dropdown = new Dropdown("Essence selection");
		$key = 0;
		foreach($player->getInventory()->getContents() as $item){
			if($item instanceof EssenceOfSuccess && !$item->isRaw()){
				$this->essences[$key] = $item;
				$msgStart = ($item->getCount() > 1 ? TF::WHITE . $item->getCount() . "x " : "");
				$dropdown->addOption($msgStart . $item->getName() . TF::RESET . TF::YELLOW . " (Increase: " . $item->getPercent() . '%%)');
				$key++;
			}
		}
		$this->addElement($dropdown);
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if(empty($this->essences) || empty($this->items)) return;

		$item = $this->items[$response[1]];
		$slot = $player->getInventory()->first($item, true);
		if($slot === -1){
			$player->sendMessage(TF::RI . "This item is no longer in your inventory!");
			return;
		}
		
		$essence = $this->essences[$response[3]];
		$slot = $player->getInventory()->first($essence, true);
		if($slot === -1){
			$player->sendMessage(TF::RI . "This essence is no longer in your inventory!");
			return;
		}
		if($essence->getCost() > $player->getGameSession()->getEssence()->getEssence()){
			$player->sendMessage(TF::RI . "You don't have enough essence to use this essence of success!");
			return;
		}

		$player->showModal(new ConfirmIncreaseUI($item, $essence));
	}
}