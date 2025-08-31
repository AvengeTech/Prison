<?php namespace prison\enchantments\uis\enchanter;

use pocketmine\player\Player;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\{
	Armor,
	Bow,
	Shovel,
	Hoe,
	Pickaxe,
	Axe,
	Shears,
	Sword,
	Durable
};

use prison\Prison;
use prison\PrisonPlayer;
use prison\enchantments\book\RedeemedBook;
use prison\enchantments\EnchantmentData;
use prison\item\NetheriteSword;

use core\Core;
use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Dropdown
};

use core\utils\TextFormat;
use prison\fishing\item\FishingRod;

class SelectItemUi extends CustomForm{

	const SWORD_LIMIT = 5;
	const ROD_LIMIT = 2;
	const BOW_LIMIT = 1;
	const ARMOR_LIMIT = 6;
	const TOOL_LIMIT = 8;

	public $items = [];
	/** @var RedeemedBook[] $enchantments */
	public $enchantments = [];

	public function __construct(Player $player){
		parent::__construct("Enchant Item");

		$this->addElement(new Label("Which item are you trying to enchant?"));

		$dropdown = new Dropdown("Item selection");
		$key = 0;
		foreach($player->getInventory()->getContents() as $item){
			if($item instanceof Durable){
				$this->items[$key] = $item;
				$dropdown->addOption($item->getName() . TextFormat::RESET . TextFormat::WHITE . " (" . $item->getDamage() . " uses)");
				$key++;
			}
		}
		if(empty($this->items)){
			$dropdown->addOption("You have nothing to enchant!");
		}
		$this->addElement($dropdown);

		$this->addElement(new Label("Select what enchantment you would like to apply to this item"));

		$dropdown = new Dropdown("Enchantment selection");
		$key = 0;
		foreach($player->getInventory()->getContents() as $item){
			try{
				if($item instanceof RedeemedBook){
					$this->enchantments[$key] = $item;
					$dropdown->addOption($item->getName() . TextFormat::RESET . TextFormat::AQUA . " (" . $item->getEnchant()->getTypeName() . "enchantment)");
					$key++;
				}
			}catch(\Exception $e){
				Core::getInstance()->getLogger()->logException($e);
			}
		}
		if(empty($this->enchantments)){
			$dropdown->addOption("You have no enchantment books!");
		}
		$this->addElement($dropdown);
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if(empty($this->items) || empty($this->enchantments)){
			return;
		}
		$item = $this->items[$response[1]];
		$book = $this->enchantments[$response[3]];
		if(!EnchantmentData::canEnchantWith($item, $book->getEnchant())){
			$player->sendMessage(TextFormat::RN . "You cannot apply a/n " . TextFormat::AQUA . $book->getEnchant()->getTypeName() . "enchantment" . TextFormat::GRAY . " to this item!");
			return;
		}

		if($item instanceof Bow){
			$player->sendMessage(TextFormat::RN . "You cannot apply bow enchantments!");
			return;
		}

		$ench = EnchantmentIdMap::getInstance()->fromId(($se = $book->getEnchant())->getId());
		if($item->hasEnchantment($ench)){
			$enchantment = $item->getEnchantment($ench);
			if($enchantment->getLevel() >= $se->getMaxLevel()){
				$player->sendMessage(TextFormat::RN . "This item already has this enchantment at it's highest level!");
				return;
			}
			if($book->getEnchant()->getStoredLevel() <= $enchantment->getLevel()){
				$player->sendMessage(TextFormat::RN . "This item already has this enchantment!");
				return;
			}
			if($book->getEnchant()->getStoredLevel() > $enchantment->getLevel() + 1 && !$book->canSkipTiers()){
				$player->sendMessage(TextFormat::RN . "You must apply " . $book->getEnchant()->getLore($book->getEnchant()->getStoredLevel() - 1) . TextFormat::RESET . TextFormat::GRAY . " before applying this book!");
				return;
			}
		}else{
			if(
				count($item->getEnchantments()) >= self::SWORD_LIMIT &&
				($item instanceof Sword)
			){
				$player->sendMessage(TextFormat::RN . "Swords can only have a maximum of " . self::SWORD_LIMIT . " enchantments applied!");
				return;
			}
			if(
				count($item->getEnchantments()) >= self::ROD_LIMIT &&
				$item instanceof FishingRod
			){
				$player->sendMessage(TextFormat::RN . "Fishing Rods can only have a maximum of " . self::ROD_LIMIT . " enchantments applied!");
				return;
			}
			if(
				count($item->getEnchantments()) >= self::TOOL_LIMIT &&
				($item instanceof Hoe || $item instanceof Shovel || $item instanceof Pickaxe || $item instanceof Axe || $item instanceof Shears)
			){
				$player->sendMessage(TextFormat::RN . "Tools can only have a maximum of " . self::TOOL_LIMIT . " enchantments applied!");
				return;
			}

			if(
				count($item->getEnchantments()) >= self::ARMOR_LIMIT &&
				$item instanceof Armor
			){
				$player->sendMessage(TextFormat::RN . "Armor can only have a maximum of " . self::ARMOR_LIMIT . " enchantments applied!");
				return;
			}
			if($book->getEnchant()->getStoredLevel() !== 1 && !$book->canSkipTiers()){
				$player->sendMessage(TextFormat::RN . "You must apply " . $book->getEnchant()->getLore(1) . TextFormat::RESET . TextFormat::GRAY . " before applying this book!");
				return;
			}
		}

		$player->showModal(new EnchantConfirmUi($item, $book));
	}

}