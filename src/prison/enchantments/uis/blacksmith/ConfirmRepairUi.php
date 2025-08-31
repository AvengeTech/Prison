<?php namespace prison\enchantments\uis\blacksmith;

use pocketmine\player\Player;
use pocketmine\item\Item;
use pocketmine\data\bedrock\item\UnsupportedItemTypeException;
use pocketmine\item\Durable;

use prison\PrisonPlayer;

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;

class ConfirmRepairUi extends ModalWindow{

	public $item;
	public $price;

	public function __construct(Item $item){
		if (!$item instanceof Durable) return throw new UnsupportedItemTypeException("Item must be of type " . Durable::class . "!");
		$this->item = $item;
		$this->price = ceil(min(30, $item->getDamage() / 25) + (count($item->getEnchantments()) * 2));

		parent::__construct("Confirm Repair", "Repairing this item will cost " . TextFormat::YELLOW . $this->price . " XP Levels" . TextFormat::WHITE . ", are you sure you want to repair this item?", "Repair item", "Go back");
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if($response){
			$item = $player->getInventory()->first($this->item, true);
			if($item == -1){
				$player->sendMessage(TextFormat::RN . "Item you're trying to repair no longer exists in inventory!");
				return;
			}

			if($player->getXpManager()->getXpLevel() < $this->price){
				$player->sendMessage(TextFormat::RN . "You do not have enough XP Levels to repair this!");
				return;
			}

			$slot = $item;
			/** @var Durable */
			$item = $player->getInventory()->getItem($slot);
			$item->setDamage(0);
			
			$player->getInventory()->setItem($slot, $item);
			$player->getXpManager()->subtractXpLevels($this->price);
			$player->sendMessage(TextFormat::GI . "Successfully repaired this item!");
		}else{
			$player->showModal(new RepairItemUi($player));
		}
	}

}