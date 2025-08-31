<?php

namespace prison\mysteryboxes\uis;

use core\AtPlayer;
use core\ui\elements\simpleForm\Button;
use core\ui\windows\SimpleForm;
use core\utils\TextFormat as TF;
use prison\mysteryboxes\pieces\MysteryBoxFilter;
use prison\PrisonPlayer;

class OpenBoxFilterUI extends SimpleForm{

	public function __construct(AtPlayer $player, string $content = ""){
		/** @var PrisonPlayer $player */
		$session = $player->getGameSession()->getMysteryBoxes();

		if(!$session->getFilter()->isEnabled()){
			$content .= TF::GRAY . "The filter is currently " . TF::RED . "DEACTIVATED" . TF::GRAY . ". None of the rewards will be filtered.";
		}else{
			if($session->getFilter()->isAutoClearing()){
				$content .= TF::GRAY . "The filter is " . TF::BOLD . TF::DARK_RED . "AUTO-CLEARING" . TF::RESET . TF::GRAY . " all rewards.";
			}else{
				$content .= TF::GRAY . "The filter is at " . TF::GOLD . $session->getFilter()->getCount() . TF::WHITE . "/" . TF::GOLD . $session->getFilter()->getSize($player->getRank()) . TF::GRAY . " items that it can hold.\n\n";
				$content .= TF::GRAY . "If the filter is full, rewards will not be filtered.";
			}
		}

		parent::__construct("Crate Filter", $content);

		$this->addButton(new Button(TF::BOLD . ($session->getFilter()->isEnabled() ? TF::RED . "Deactivate" : TF::GREEN . "Activate")));

		if($session->getFilter()->isEnabled()){
			$this->addButton(new Button(TF::BOLD . TF::DARK_GRAY . "Edit Filter"));

			if($session->getFilter()->getCount() && !$session->getFilter()->isAutoClearing()){
				$this->addButton(new Button(TF::BOLD . TF::DARK_RED . "Clear Filter"));
			}
		}
	}

	public function handle($response, AtPlayer $player){
		/** @var PrisonPlayer $player */
		$session = $player->getGameSession()->getMysteryBoxes();

		if($response === 0){
			$session->getFilter(true)->setEnabled(!$session->getFilter()->isEnabled());

			if($session->getFilter()->isEnabled()){
				$player->showModal(new self($player, TF::GREEN . "Activated the filter!\n\n"));
			}else{
				$player->showModal(new self($player, TF::GREEN . "Deactivated the filter!\n\n"));
			}

		}elseif($response === 1){
			$player->showModal(new EditFilterSettingsUI($player));
		}elseif($response === 2){
			$player->getGameSession()->getTechits()->addTechits($session->getFilter()->getInventoryValue());

			$player->sendMessage(TF::GI . "Sold your filter items for " . TF::AQUA . $session->getFilter()->getInventoryValue() . " techits" . TF::GRAY . '.');

			$session->getFilter(true)->setCount(0);
			$session->getFilter(true)->setInventoryValue(0);
		}
	}
}