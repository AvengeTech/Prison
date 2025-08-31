<?php namespace prison\enchantments\uis\blacksmith;

use pocketmine\player\Player;

use prison\PrisonPlayer;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\ItemRegistry;

class BlacksmithUi extends SimpleForm{

	public function __construct(Player $player){
		parent::__construct("Blacksmith", "What do you need today? Tap an option below to modify an item!");

		$this->addButton(new Button("Add Death Message"));
		$this->addButton(new Button("Rename Item"));
		$this->addButton(new Button("Repair Item"));
		$this->addButton(new Button("Remove Enchantment"));
		$this->addButton(new Button("Add Item Animation"));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if($response == 0){
			$box = ItemRegistry::CUSTOM_DEATH_TAG();
			$box->init();
			$box = $player->getInventory()->first($box);
			if($box == -1){
				$player->sendMessage("Your inventory must contain a Custom Death Tag to do this!");
				return;
			}
			$player->showModal(new DeathMessageUi($player));
			return;
		}
		if($response == 1){
			$nt = ItemRegistry::NAMETAG();
			$nt->init();
			$nt = $player->getInventory()->first($nt);
			if($nt == -1){
				$player->sendMessage("Your inventory must contain a Nametag to do this!");
				return;
			}
			$player->showModal(new RenameItemUi($player));
			return;
		}
		if($response == 2){
			$player->showModal(new RepairItemUi($player));
			return;
		}
		if($response == 3){
			$player->showModal(new RemoveEnchantmentUi($player));
			return;
		}
		if($response == 4){
			$player->showModal(new AnimatorUi($player));
			return;
		}
	}

}