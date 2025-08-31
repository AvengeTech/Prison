<?php

namespace prison\auctionhouse\ui\manage;

use pocketmine\player\Player;
use pocketmine\item\Durable;

use prison\PrisonPlayer;
use prison\gangs\battle\BattleKit;
use prison\enchantments\effects\items\EffectItem;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\conversion\LegacyItemIds;
use core\utils\TextFormat;
use pocketmine\item\VanillaItems;

class ChooseAuctionItemUi extends SimpleForm {

	public $items = [];

	public function __construct(Player $player, string $message = "", bool $error = true) {
		parent::__construct(
			"Choose Auction Item",
			($message ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
				"Pick the item from your inventory you're wanting to put up for auction."
		);

		for ($i = 0; $i <= $slots = $player->getInventory()->getSize() - 4; $i++) {
			$item = $player->getInventory()->getItem($i);
			if ($item->getTypeId() != VanillaItems::AIR()->getTypeId() && $item->getCount() > 0 && $item->getNamedTag()->getTag(BattleKit::BATTLE_TAG) === null && (!$item instanceof EffectItem || $item->getEffectId() != 0)) {
				$this->items[] = $item;
			}
		}

		foreach ($this->items as $item) {
			$button = new Button("x" . $item->getCount() . " " . $item->getName());
			$button->addImage("url", "[REDACTED]" . LegacyItemIds::typeIdToLegacyId($item->getTypeId()) . "-" . LegacyItemIds::stateIdToMeta($item) . ".png");
			$this->addButton($button);
		}

		$this->addButton(new Button("Go back"));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		foreach ($this->items as $key => $item) {
			if ($key == $response) {
				if ($item->isNull()) {
					$player->showModal(new ChooseAuctionItemUi($player, "This item cannot be put up for auction."));
					return;
				}
				$player->showModal(new CreateAuctionUi($item));
				return;
			}
		}
		$player->showModal(new AuctionManageUi($player));
	}
}
