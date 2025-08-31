<?php namespace prison\enchantments\type;

use prison\enchantments\EnchantmentData;

class StackableEnchantment extends Enchantment{

	public function isStackable() : bool{
		return EnchantmentData::ENCHANTMENTS[$this->getId()]["stackable"] ?? false;
	}

	public function getMaxStackLevel() : int{
		if(!$this->isStackable()) return -1;
		return EnchantmentData::ENCHANTMENTS[$this->getId()]["stackLevel"] ?? 1;
	}

}