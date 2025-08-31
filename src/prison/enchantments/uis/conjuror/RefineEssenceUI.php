<?php

namespace prison\enchantments\uis\conjuror;

use core\ui\elements\customForm\Dropdown;
use core\ui\elements\customForm\Label;
use core\ui\windows\CustomForm;
use core\utils\TextFormat as TF;
use pocketmine\player\Player;
use prison\enchantments\uis\conjuror\confirm\ConfirmRefineEssenceUI;
use prison\item\Essence;
use prison\item\EssenceOfAscension;
use prison\item\EssenceOfKnowledge;
use prison\item\EssenceOfSuccess;
use prison\PrisonPlayer;

class RefineEssenceUI extends CustomForm{

	private array $items = [];

	public function __construct(Player $player){
		parent::__construct("Select Essenece To Refine");

		$this->addElement(new Label("Which piece of essence would you like to refine?"));

		$dropdown = new Dropdown("Essence Selection");
		$key = 0;
		foreach($player->getInventory()->getContents() as $item){
			if($item instanceof Essence && $item->isRaw()){
				$this->items[$key] = $item;
				$msgStart = ($item->getCount() > 1 ? TF::WHITE . $item->getCount() . "x " : "");

				if($item instanceof EssenceOfSuccess){
					$dropdown->addOption($msgStart . $item->getName() . TF::RESET . TF::GRAY . "(" . TF::RED . $item->getRarityPercentages(-1, false) . '%%' . TF::GRAY . ' - ' . TF::GREEN . $item->getRarityPercentages(-1, false, false) . '%%' . TF::GRAY . ")");
				}elseif($item instanceof EssenceOfAscension){
					$dropdown->addOption($msgStart . $item->getName() . TF::RESET . TF::GRAY . "(" . TF::BOLD . substr($item->getRarityName(), 0, 4) . TF::RESET . TF::GRAY . ")");
				}else{
					$dropdown->addOption($msgStart . $item->getName());
				}
				$key++;
			}
		}

		$this->addElement($dropdown);
		$this->addElement(new Label("Press 'Submit' to calculate how much to refine this piece of essence is going to cost"));
	}
	
	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		if(empty($this->items)) return;

		/** @var EssenceOfSuccess|EssenceOfKnowledge $item */
		$item = $this->items[$response[1]];

		$player->showModal(new ConfirmRefineEssenceUI($item));
	}
}