<?php namespace prison\enchantments\uis\blacksmith;

use pocketmine\player\Player;
use pocketmine\item\Item;

use prison\PrisonPlayer;

use core\ui\windows\ModalWindow;
use core\utils\ItemRegistry;
use core\utils\TextFormat;

class ConfirmRenameUi extends ModalWindow{

	public $item;
	public $text;

	public $price;

	public function __construct(Item $item, $text){
		$this->item = $item;
		$this->text = $text;

		$this->price = strlen($text);

		parent::__construct("Confirm Rename", "Renaming this item will cost " . TextFormat::YELLOW . $this->price . " XP Levels" . TextFormat::WHITE . ", are you sure you want to rename your item to " . $this->text, "Rename Item", "Go back");
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if($response){
			$nt = ItemRegistry::NAMETAG()->setCount(1);
			$nt->init();
			$ntSlot = $player->getInventory()->first($nt);
			if($ntSlot == -1){
				$player->sendMessage(TextFormat::RN . "Your inventory must contain a " . TextFormat::AQUA . "Nametag" . TextFormat::GRAY . " to do this!");
				return;
			}

			$slot = $player->getInventory()->first($this->item, true);
			if($slot == -1){
				$player->sendMessage(TextFormat::RN . "Item you're trying to rename no longer exists in inventory!");
				return;
			}

			if($player->getXpManager()->getXpLevel() < $this->price){
				$player->sendMessage(TextFormat::RN . "You do not have enough XP Levels to rename this!");
				return;
			}

			$item = $player->getInventory()->getItem($slot);
			$item->setCustomName(TextFormat::RESET . $this->text);

			$player->getInventory()->setItem($slot, $item);
			$player->getInventory()->removeItem($nt);
			$player->getXpManager()->subtractXpLevels($this->price);
			$player->sendMessage(TextFormat::GI . "Successfully renamed your item to " . $this->text);
		}else{
			$player->showModal(new RenameItemUi($player));
		}
	}

}