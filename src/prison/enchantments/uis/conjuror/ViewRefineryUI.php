<?php

namespace prison\enchantments\uis\conjuror;

use core\AtPlayer;
use core\ui\elements\simpleForm\Button;
use core\ui\windows\SimpleForm;
use core\utils\ItemRegistry;
use core\utils\TextFormat as TF;
use pocketmine\player\Player;
use prison\enchantments\inventory\RefineEssenceInventory;
use prison\item\EssenceOfAscension;
use prison\item\EssenceOfSuccess;
use prison\PrisonPlayer;

class ViewRefineryUI extends SimpleForm{

	public function __construct(PrisonPlayer $player, string $label = ""){
		parent::__construct("Refinery", $label . "You can check out all of the essence you are refining here!");

		$this->addButton(new Button(TF::RED . "Go Back"));

		$session = $player->getGameSession()->getEssence();

		if(!empty($session->getRefineryInventory())) $this->addButton(new Button(TF::BLUE . "Take All"));

		foreach($session->getRefineryInventory() as $key => $data){
			$data = explode(":", $data);
			$essenceType = (string) $data[0];
			$essenceRarity = (int) $data[1];

			$essence = match($essenceType){
				"s" => ItemRegistry::ESSENCE_OF_SUCCESS(),
				"k" => ItemRegistry::ESSENCE_OF_KNOWLEDGE(),
				"p" => ItemRegistry::ESSENCE_OF_PROGRESS(),
				"a" => ItemRegistry::ESSENCE_OF_ASCENSION()
			};
			$essence->setup($essenceRarity); // do not add the init, it will stop showing the time on the button

			$buttonName = $essence->getName();
			if($essence instanceof EssenceOfSuccess){
				$buttonName .= TF::RESET . TF::DARK_GRAY . "(" . TF::RED . $essence->getRarityPercentages(-1, false) . '%%' . TF::DARK_GRAY . '-' . TF::GREEN . $essence->getRarityPercentages(-1, false, false) . '%%' . TF::DARK_GRAY . ")";
			}elseif($essence instanceof EssenceOfAscension){
				$buttonName .= TF::RESET . TF::DARK_GRAY . "(" . TF::BOLD . substr($essence->getRarityName(), 0, 4) . TF::RESET . TF::DARK_GRAY . ")";
			}

			$buttonName .= "\n" . TF::BOLD . ($session->hasTimeLeft($key) ? TF::GOLD . "Time Left: " . TF::RESET . TF::YELLOW . $session->getFormattedTime($key) : TF::GREEN . "READY!");

			$this->addButton(new Button(
				$buttonName, 
				"path", 
				"textures/items/essence_of_" . ($essenceType === "s" ? "success" : ($essenceType === "k" ? "knowledge" : ($essenceType === "p" ? "progress" : "ascension")))
			));
		}
	}

	public function handle($response, AtPlayer $player){
		/** @var PrisonPlayer $player */
		if($response === 0){
			$player->showModal(new ConjurorUI($player));
		}elseif($response === 1){
			$session = $player->getGameSession()->getEssence();
			$take = [];

			$refinery = array_reverse($session->getRefineryInventory(), true);

			foreach($refinery as $key => $data){
				if($session->hasTimeLeft($key)) continue;

				$data = explode(":", $data);
				$essenceType = $data[0];
				$essenceRarity = $data[1] ?? 1;

				$item = match($essenceType){
					"s" => ItemRegistry::ESSENCE_OF_SUCCESS()->setup($essenceRarity, -1, -1, false),
					"k" => ItemRegistry::ESSENCE_OF_KNOWLEDGE()->setup($essenceRarity, -1, false),
					"p" => ItemRegistry::ESSENCE_OF_PROGRESS()->setup($essenceRarity, -1, false),
					"a" => ItemRegistry::ESSENCE_OF_ASCENSION()->setup($essenceRarity, false)
				};
				
				$item->init();

				if(!$player->getInventory()->canAddItem($item)) continue;

				$session->removeFromInventory($key);
				$take[] = $item;
			}

			if(empty($take)){
				$player->showModal(new self($player, TF::RED . "Could not take any, all your essence is still refining!\n" . TF::WHITE));
				return;
			}

			foreach($take as $takenItem){
				$player->getInventory()->addItem($takenItem);
			}
			
			$player->sendMessage(TF::GI . "You took " . (count($session->getRefineryInventory()) > 1 ? "all the" : "as much") ." refined essence from your refinery" . (count($session->getRefineryInventory()) === 0 ? "" : " that could fit in your inventory") . ".");
		}else{
			$session = $player->getGameSession()->getEssence();
			$key = $response - 2;

			if($session->hasTimeLeft($key)){
				$player->showModal(new self($player, TF::RED . "That piece of essence is not ready to collect!\n" . TF::WHITE));
			}else{
				$player->showModal(new CollectEssenceUI($key));
			}
		}
	}
}