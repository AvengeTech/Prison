<?php namespace prison\gangs\shop;

use pocketmine\player\Player;
use pocketmine\item\Item;

use prison\Prison;
use prison\gangs\objects\Gang;
use prison\enchantments\book\RedeemedBook;
use prison\enchantments\EnchantmentData;

use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class ShopStock{

	public $item;

	public $price;
	public $extra;

	public function __construct(Item $item, int $price, mixed $extra = -1){
		$this->item = $item;

		$this->price = $price;
		$this->extra = $extra;
	}

	public function getName() : string{
		$item = $this->getItem();
		if($item instanceof RedeemedBook){
			return "Random " . EnchantmentData::RARITY_NAMES[$this->getExtra()] . " Enchantment";
		}
		return TextFormat::clean($item->getName());
	}

	public function getItem() : Item{
		return clone $this->item;
	}

	public function getPrice() : int{
		return $this->price;
	}

	public function getExtra() : mixed{
		return $this->extra;
	}

	public function canAfford(Gang $gang) : bool{
		return $gang->getBankValue() >= $this->getPrice();
	}

	public function buy(Player $player, Gang $gang) : void{
		$item = $this->getItem();
		if($item instanceof RedeemedBook){
			$rarity = $this->getExtra();
			$ench = Prison::getInstance()->getEnchantments()->getRandomEnchantment($rarity);
			$ench->setStoredLevel(mt_rand(1, $ench->getMaxLevel()));
			$item->setup($ench);
		}
		$player->getInventory()->addItem($item);
		$gang->takeTrophies($this->getPrice());
	}

	public function getButton() : Button{
		return new Button("x" . $this->getItem()->getCount() . " " . $this->getName() . PHP_EOL . $this->getPrice() . " trophies");
	}

}