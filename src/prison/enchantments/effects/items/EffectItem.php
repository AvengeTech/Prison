<?php namespace prison\enchantments\effects\items;

use pocketmine\item\ItemUseResult;
use pocketmine\player\Player;
use pocketmine\item\Item;
use pocketmine\math\Vector3;

use prison\Prison;
use prison\enchantments\EnchantmentData;
use prison\enchantments\effects\EffectClass;

use core\utils\TextFormat;

class EffectItem extends Item{

	public int $rarity = 0;

	public function getMaxStackSize() : int{
		return 1;
	}

	public function getRarity() : int{
		return $this->rarity;
	}

	public function getRarityName(int $rarity = -1, ?EffectClass $effect = null) : string{
		if($rarity == -1) $rarity = $this->getRarity();
		$effect = $effect ?? $this->getEffect();
		if($effect == null){ $name = "Unknown"; }else{ $name = $effect->getName(); }

		switch($rarity){
			case EnchantmentData::RARITY_COMMON:
				return TextFormat::GREEN . $name . " Animator";
			case EnchantmentData::RARITY_UNCOMMON:
				return TextFormat::DARK_GREEN . $name . " Animator";
			case EnchantmentData::RARITY_RARE:
				return TextFormat::YELLOW . $name . " Animator";
			case EnchantmentData::RARITY_LEGENDARY:
				return TextFormat::GOLD . $name . " Animator";
			case EnchantmentData::RARITY_DIVINE:
				return TextFormat::RED . $name . " Animator";
		}
		return " ";
	}

	public function getApplyCost() : int{
		return $this->getNamedTag()->getInt("applycost", 0);
	}

	public function getEffectId() : int{
		return $this->getNamedTag()->getInt("effectid", 0);
	}

	public function getEffect() : ?EffectClass{
		$effect = ($effs = Prison::getInstance()->getEnchantments()->getEffects())->getEffectById($this->getEffectId());
		if($effect === null){
			$name = explode(" ", $this->getCustomName());
			array_pop($name);
			$name = TextFormat::clean(implode(" ", $name));
			$effect = $effs->getEffectByName($name);
		}
		return $effect;
	}

	public function getRandomCost() : int{
		return $this->getRarity() * mt_rand(6, 9);
	}

	public function setup(int $rarity = 1, ?EffectClass $eff = null, int $cost = -1) : void{
		$this->rarity = $rarity;
		$effect = $eff ?? Prison::getInstance()->getEnchantments()->getEffects()->getRandomEffect($this->getRarity());

		$this->getNamedTag()->setLong("item_random_id", (int)(time() * mt_rand(-999, 999)));

		// Temp fix: Effect comes out as null for some reason...?
		while($effect === null){
			$effect = Prison::getInstance()->getEnchantments()->getEffects()->getRandomEffect($this->getRarity());
		}

		$cost = ($cost == -1 ? $this->getRandomCost() : $cost);

		$nbt = $this->getNamedTag();
		$nbt->setInt("effectid", $effect->getId());
		$nbt->setInt("applycost", $cost);
		$this->setNamedTag($nbt);

		$this->setCustomName(TextFormat::RESET . TextFormat::BOLD . $this->getRarityName($effect->getRarity(), $effect));

		$lores = [];
		$lores[] = TextFormat::GRAY . wordwrap("Description: " . $effect->getDescription(), 36, "\n" . TextFormat::GRAY);
		$lores[] = TextFormat::GRAY . "Type: " . $effect->getTypeName() . " Animation";
		$lores[] = " ";
		$lores[] = TextFormat::YELLOW . "Apply cost: " . $cost . " XP Levels";
		$lores[] = " ";
		$lores[] = TextFormat::GRAY . "Bring this book to the " . TextFormat::DARK_GRAY . TextFormat::BOLD . "Blacksmith";
		$lores[] = TextFormat::GRAY . "at the " . TextFormat::WHITE . "Hangout" . TextFormat::GRAY . " to add this animation";
		$lores[] = TextFormat::GRAY . "to one of your tools!";
		foreach($lores as $key => $lore) $lores[$key] = TextFormat::RESET . $lore;

		$this->setLore($lores);
	}

	public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems): ItemUseResult{
		if($this->getEffectId() === 0){
			$effect = $this->getEffect();
			if($effect !== null){
				$this->setup($this->rarity, $effect);
				$player->sendMessage(TextFormat::GI . "Animator has been updated!");
				return ItemUseResult::SUCCESS();
			}else{
				$player->sendMessage(TextFormat::RI . "This animator could not be updated!");
				return ItemUseResult::FAIL();
			}
		}
		return ItemUseResult::FAIL();
	}

}