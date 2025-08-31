<?php

namespace prison\enchantments\uis\conjuror\confirm;

use core\AtPlayer;
use core\ui\windows\ModalWindow;
use core\utils\TextFormat as TF;
use prison\enchantments\EnchantmentData as ED;
use prison\enchantments\uis\conjuror\RefineEssenceUI;
use prison\item\Essence;
use prison\PrisonPlayer;

class ConfirmRefineEssenceUI extends ModalWindow{

	private int $price;

	public function __construct(
		private Essence $essence
	){
		$this->price = match($essence->getType()){
			"s" => 10 + (10 * $essence->getRarity()),
			"k" => 50,
			"p" => 100,
			"a" => 40 + (10 * $essence->getRarity()) + ($essence === ED::RARITY_DIVINE ? 10 : 0)
		};

		parent::__construct(
			"Confirm Refine", 
			"Refining this piece of essence will cost " . TF::DARK_AQUA . $this->price . " Essence" . TF::WHITE . ", are you sure you want to refine this piece of essence?",
			"Refine Essence",
			"Go Back"
		);
	}

	public function handle($response, AtPlayer $player){
		/** @var PrisonPlayer $player */
		if($response){
			$slot = $player->getInventory()->first($this->essence, true);
			if($slot == -1){
				$player->sendMessage(TF::RN . "Essence you're trying to refine no longer exists in inventory!");
				return;
			}

			if($player->getGameSession()->getEssence()->getEssence() < $this->price){
				$player->sendMessage(TF::RN . "You do not have enough essence to refine this!");
				return;
			}

			$rarity = $this->essence->getRarity();
			$minutes = (5 + ($rarity === ED::RARITY_DIVINE ? $rarity : $rarity - 1));

			/** @var PrisonPlayer $player */
			$player->getGameSession()->getEssence()->addToInventory($this->essence, time() + ($minutes * 60)); // 10 mins for divine, 5 - 8mins for rest
			$this->essence->pop();

			$player->getInventory()->setItem($slot, $this->essence);
			$player->getGameSession()->getEssence()->subEssence($this->price);
			$player->sendMessage(TF::GI . "This piece of essence is now refining, come back in " . TF::YELLOW . $minutes . TF::GRAY . " minutes to collect your refined essence!");
		}else{
			$player->showModal(new RefineEssenceUI($player));
		}
	}
}