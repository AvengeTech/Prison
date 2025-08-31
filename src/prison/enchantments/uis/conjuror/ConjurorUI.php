<?php

namespace prison\enchantments\uis\conjuror;

use core\AtPlayer;
use core\ui\elements\simpleForm\Button;
use core\ui\windows\SimpleForm;
use core\utils\ItemRegistry;
use core\utils\TextFormat as TF;
use pocketmine\player\Player;
use prison\item\EssenceOfKnowledge;
use prison\item\EssenceOfProgress;
use prison\item\EssenceOfSuccess;
use prison\PrisonPlayer;

class ConjurorUI extends SimpleForm{

	public function __construct(Player $player){
		/** @var PrisonPlayer $player */
		parent::__construct("Conjuror", "What do you need today? Tap an option below to modify an item!");

		$isRefining = false;

		foreach($player->getGameSession()->getEssence()->getRefineryInventory() as $key => $data){
			if($player->getGameSession()->getEssence()->hasTimeLeft($key)){
				$isRefining = true;
				break;
			}
		}

		$this->addButton(new Button("View Refinery", "path", "textures/blocks/furnace_front_" . ($isRefining ? "on" : "off")));
		$this->addButton(new Button("Refine Essence"));
		$this->addButton(new Button("Increase Chances"));
		$this->addButton(new Button("Combine Books"));
		$this->addButton(new Button("Reroll Book"));
		$this->addButton(new Button("Progress Book"));
		$this->addButton(new Button("Ascend Enchantment"));
	}

	public function handle($response, AtPlayer $player){
		if($response === 0){
			$player->showModal(new ViewRefineryUI($player));
		}elseif($response === 1){
			$player->showModal(new RefineEssenceUI($player));
		}elseif($response === 2){
			$eos = null;

			foreach ($player->getInventory()->getContents() as $item) {
				/** @var EssenceOfSuccess $item */
				if ($item->equals(ItemRegistry::ESSENCE_OF_SUCCESS(), false, false) && !$item->isRaw()) {
					$eos = $item;
					break;
				}
			}

			if (is_null($eos)) {
				$player->sendMessage(TF::RI . "Your inventory must contain " . TF::GREEN . "Essence of Success" . TF::GRAY . " to do this!");
				return;
			}

			$player->showModal(new IncreaseChancesUI($player));
		}elseif($response === 3){
			$eok = null;

			foreach ($player->getInventory()->getContents() as $item) {
				/** @var EssenceOfKnowledge $item */
				if ($item->equals(ItemRegistry::ESSENCE_OF_KNOWLEDGE(), false, false) && !$item->isRaw()) {
					$eok = $item;
					break;
				}
			}

			if (is_null($eok)) {
				$player->sendMessage(TF::RI . "Your inventory must contain " . TF::AQUA . "Essence of Knowledge" . TF::GRAY . " to do this!");
				return;
			}

			$player->showModal(new CombineBooksUI($player));
		}elseif($response === 4){
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

			$player->showModal(new RerollBookUI($player));
		}elseif($response === 5){
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

			$player->showModal(new ProgressBookUI($player));
		}elseif($response === 6){
			$player->showModal(new AscendEnchantmentUI($player));
		}
	}
}