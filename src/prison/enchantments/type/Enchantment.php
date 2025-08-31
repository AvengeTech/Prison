<?php namespace prison\enchantments\type;

use core\utils\ItemRegistry;
use pocketmine\item\Item;
use pocketmine\item\enchantment\{
	Enchantment as PMEnch,
	EnchantmentInstance
};
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\network\mcpe\protocol\serializer\BitSet;
use pocketmine\utils\TextFormat;

use prison\enchantments\EnchantmentData as ED;
use prison\enchantments\book\RedeemedBook;
use prison\Prison;

class Enchantment{

	public int $level = -1;

	public function __construct(public int $id){
		if(!$this->isProtection() && $this->getId() > 70){
			$this->register();
		}
	}

	public function register() : void{
		$ench = new PMEnch($this->getName(), $this->getRarity(), 0x0, 0x0, 1000);
		EnchantmentIdMap::getInstance()->register($this->getId(), $ench);
	}

	public function getEnchantment() : PMEnch{
		return EnchantmentIdMap::getInstance()->fromId($this->getId());
	}

	public function getEnchantmentInstance(int $level = 1) : EnchantmentInstance{
		$enchantment = $this->getEnchantment();
		return new EnchantmentInstance($enchantment, $level);
	}

	public function getId() : int{
		return $this->id;
	}

	public function getRuntimeId() : int{
		return spl_object_id($this->getEnchantment());
	}

	public function getName() : string{
		return ED::ENCHANTMENTS[$this->getId()]["name"];
	}

	public function getType(): BitSet {
		$basicType = ED::ENCHANTMENTS[$this->getId()]["type"] ?? ED::SLOT_ALL;
		if (!is_array($basicType)) $basicType = [$basicType];

		$set = new BitSet(count(ED::SLOTS) * (PHP_INT_SIZE * 8), []);
		foreach ($basicType as $f) $set->set($f, true);

		return $set;
	}

	public function getTypeName(): string {
		$str = "";
		foreach ($this->getETypes() as $type) {
			if (strlen($str) > 0) $str .= " & ";
			$str .= ED::ITEM_FLAG_NAMES[$type];
		}
		return $str . " ";
	}

	public function isProtection() : bool{
		return isset(ED::ENCHANTMENTS[$this->getId()]["protection"]) ? ED::ENCHANTMENTS[$this->getId()]["protection"] : false;
	}

	public function isStackable() : bool{
		return false;
	}

	public function isObtainable(): bool {
		return isset(ED::ENCHANTMENTS[$this->getId()]["obtainable"]) ? ED::ENCHANTMENTS[$this->getId()]["obtainable"] : true;
	}

	public function getDescription() : string{
		return ED::ENCHANTMENTS[$this->getId()]["description"];
	}

	public function getMaxLevel() : int{
		return ED::ENCHANTMENTS[$this->getId()]["maxLevel"];
	}

	public function getRarity() : int{
		return ED::ENCHANTMENTS[$this->getId()]["rarity"];
	}

	public function canOverclock() : bool{
		return isset(ED::ENCHANTMENTS[$this->getId()]["overclock"]) ? ED::ENCHANTMENTS[$this->getId()]["overclock"] : true;
	}

	public function getETypes(): array {
		return ED::typeToEtype($this->getType());
	}

	public function hasType(int $flag): bool {
		foreach ($this->getETypes() as $f) {
			if (($f & $flag) !== 0) return true;
		}
		return false;
	}

	public function getStoredLevel() : int{
		return $this->level;
	}
	
	public function setStoredLevel(int $level) : self{
		$this->level = $level;
		return $this;
	}

	/**
	 * Mostly for vanilla enchantments, whether they are handled at all by the plugin
	 */
	public function isHandled() : bool{
		return isset(ED::ENCHANTMENTS[$this->getId()]["handled"]) ? ED::ENCHANTMENTS[$this->getId()]["handled"] : true;
	}

	public function isDisabled() : bool{
		return isset(ED::ENCHANTMENTS[$this->getId()]["disabled"]) ? ED::ENCHANTMENTS[$this->getId()]["disabled"] : false;
	}

	public function getLore(int $level = 1) : string{
		return TextFormat::RESET . $this->getRarityColor($this->getRarity()) . $this->getName() . " " . Prison::getInstance()->getEnchantments()->getRoman($level);
	}

	public function getRarityColor() : string{
		return ED::rarityColor($this->getRarity());
	}

	public function getRarityName() : string{
		switch($this->getRarity()){
			case 1:
				$name = "Common";
				break;
			case 2:
				$name = "Uncommon";
				break;
			case 3:
				$name = "Rare";
				break;
			case 4:
				$name = "Legendary";
				break;
			case 5:
				$name = "Divine";
				break;
		}
		return $this->getRarityColor() . $name;
	}

	public function asBook(bool $skipTiers = false) : RedeemedBook{
		$book = ItemRegistry::REDEEMED_BOOK();
		$book->setup($this, -1, -1, $skipTiers);
		return $book;
	}

}