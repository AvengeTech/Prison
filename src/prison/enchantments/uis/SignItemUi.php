<?php namespace prison\enchantments\uis;

use pocketmine\player\Player;
use pocketmine\item\Item;

use prison\enchantments\ItemData;

use core\ui\windows\ModalWindow;

use core\utils\TextFormat;

class SignItemUi extends ModalWindow{

	public $item;

	public function __construct(Item $item){
		$this->item = $item;

		parent::__construct("Confirm Sign", "Signing this item with your username will cost " . TextFormat::YELLOW . "5 XP Levels. " . TextFormat::WHITE . "Are you sure you want to do this?" . PHP_EOL . PHP_EOL . "(NOTE: Once you sign an item, it can NOT be signed again.)", "Add Signature", "Cancel");
	}

	public function handle($response, Player $player){
		if($response){
			if(!$player->getInventory()->getItemInHand()->equals($this->item)){
				$player->sendMessage(TextFormat::RI . "You are not holding the same item as you were before!");
				return;
			}

			if($player->getXpManager()->getXpLevel() < 5){
				$player->sendMessage(TextFormat::RI . "You do not have enough XP Levels to add this death message!");
				return;
			}

			$item = $player->getInventory()->getItemInHand();
			$data = new ItemData($item);
			$data->sign($player);
			$player->getInventory()->setItemInHand($data->getItem());

			$player->getXpManager()->subtractXpLevels(5);
			$player->sendMessage(TextFormat::GI . "Successfully signed this item!");
		}
	}

}