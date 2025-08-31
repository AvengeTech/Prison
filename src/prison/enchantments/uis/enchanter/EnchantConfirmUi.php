<?php

namespace prison\enchantments\uis\enchanter;

use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use pocketmine\item\Item;

use prison\PrisonPlayer;
use prison\enchantments\book\RedeemedBook;
use prison\enchantments\ItemData;

use core\ui\windows\ModalWindow;

use core\utils\TextFormat;
use pocketmine\world\sound\AnvilBreakSound;
use pocketmine\world\sound\AnvilUseSound;

class EnchantConfirmUi extends ModalWindow {

	public $item;
	public $book;

	public function __construct(Item $item, RedeemedBook $book) {
		$this->item = $item;
		$this->book = $book;

		$content = "It will cost you " . TextFormat::YELLOW . $book->getApplyCost() . " XP Levels " . TextFormat::WHITE . "to apply this enchantment.\n\n";
		$content .= "This book has a " . TextFormat::GREEN . $book->getApplyChance() . "%%%%%" . TextFormat::WHITE . " chance to apply to this item, if failed the book will be " . TextFormat::BOLD . TextFormat::RED . "DESTROYED" . TextFormat::RESET . TextFormat::WHITE . ".\n\n";
		$content .= "Are you sure you want to apply this enchantment to this item?";
		

		parent::__construct("Confirm Enchant", $content, "Apply Enchantment", "Cancel");
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if ($response) {
			$item = $this->item;
			$book = $this->book;
			$slot1 = $player->getInventory()->first($item, true);
			$slot2 = $player->getInventory()->first($book, true);

			if ($slot1 == -1 || $slot2 == -1) {
				$player->sendMessage(TextFormat::RN . "One of the items you're trying to use is no longer in your inventory.");
				return;
			}
			if ($player->getXpManager()->getXpLevel() < $book->getApplyCost()) {
				$player->sendMessage(TextFormat::RN . "You do not have enough XP to enchant this item!");
				return;
			}

			if (mt_rand(1, 100) <= $book->getApplyChance()) {
				$data = new ItemData($player->getInventory()->getItem($slot1));
				$data->addEnchantment($book->getEnchant(), $book->getEnchant()->getStoredLevel());
				$player->getInventory()->setItem($slot1, $data->getItem());
				$book->pop();
				$player->getInventory()->setItem($slot2, $book);

				$player->getXpManager()->subtractXpLevels($book->getApplyCost());

				$player->sendMessage(TextFormat::GI . "Successfully enchanted your item!");
				$player->broadcastSound(new AnvilUseSound, [$player]);
			} else {
				$book->pop();
				$player->getInventory()->setItem($slot2, $book);

				$player->sendMessage(TextFormat::RI . "Oh no! Failed to enchant your item!");
				$player->broadcastSound(new AnvilBreakSound, [$player]);
			}
		} else {
			$player->showModal(new SelectItemUi($player));
		}
	}
}
