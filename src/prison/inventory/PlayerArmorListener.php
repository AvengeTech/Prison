<?php

namespace prison\inventory;

use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryListener;
use pocketmine\item\Item;
use prison\Prison;
use prison\PrisonPlayer;

class PlayerArmorListener implements InventoryListener {

	public function __construct(public PrisonPlayer $player) {
	}

	public function onSlotChange(Inventory $inventory, int $slot, Item $oldItem): void {
		$this->onContentChange($inventory, $inventory->getContents());
	}

	public function onContentChange(Inventory $inventory, array $oldContents): void {
		Prison::getInstance()->getEnchantments()->calculateCache($this->player);
	}
}
