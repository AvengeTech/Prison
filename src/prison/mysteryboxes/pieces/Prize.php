<?php namespace prison\mysteryboxes\pieces;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

use prison\Prison;
use prison\enchantments\book\RedeemableBook;

use prison\enchantments\effects\items\EffectItem;
use prison\item\{
	//RedeemReducer, :( :(
	CustomDeathTag,
    EssenceOfAscension,
    EssenceOfKnowledge,
    EssenceOfProgress,
    EssenceOfSuccess,
    Nametag,
	MineNuke
};
use prison\mysteryboxes\commands\KeyPack;
use prison\PrisonPlayer;

class Prize{

	public $expworth = null;

	public function __construct(
		public Item|EffectInstance|PrizeValFiller $prize,
		public int $rarity,
		public int $filter = FilterSetting::FILTER_MISCELLANEOUS
	){}

	public function getPrize(): Item|EffectInstance|PrizeValFiller {
		return $this->prize;
	}

	public function getRarity() : int{
		return $this->rarity;
	}

	public function getRarityTag(int $rarity = -1) : string{
		if($rarity == -1) $rarity = $this->getRarity();
		$rarities = [
			0 => TextFormat::GREEN . "COMMON",
			1 => TextFormat::DARK_GREEN . "UNCOMMON",
			2 => TextFormat::YELLOW . "RARE",
			3 => TextFormat::GOLD . "LEGENDARY",
			4 => TextFormat::RED . "DIVINE",
			5 => TextFormat::MINECOIN_GOLD . "VOTE",
		];
		return TextFormat::BOLD . $rarities[$rarity];
	}

	public function getName() : string{
		$prize = $this->getPrize();
		if($prize instanceof Item){
			if($prize instanceof RedeemableBook){
				$text = TextFormat::AQUA . "x" . $prize->getCount() . " ";
				switch($prize->getRarity()){
					case 1:
						return $text . "Common Book";
					case 2:
						return $text . "Uncommon Book";
					case 3:
						return $text . "Rare Book";
					case 4:
						return $text . "Legendary Book";
					case 5:
						return $text . "Divine Book";
				}
			}
			if($prize instanceof EffectItem){
				$text = TextFormat::AQUA . "x" . $prize->getCount() . " ";
				switch($prize->getRarity()){
					case 1:
						return $text . "Common Animator";
					case 2:
						return $text . "Uncommon Animator";
					case 3:
						return $text . "Rare Animator";
					case 4:
						return $text . "Legendary Animator";
					case 5:
						return $text . "Divine Animator";
				}
			}
			return "x" . $prize->getCount()  . " " . TextFormat::clean($prize->getName());
		}
		if($prize instanceof EffectInstance){
			return $prize->getType()->getName()." ".$prize->getEffectLevel()."-".($prize->getDuration() / 20)." sec";
		}
		if($prize instanceof PrizeValFiller){
			switch($prize->getName()){
				case "t":
					return $prize->getValue() . " Techits";
				case "k":
					return $prize->getValue() . " " . ucfirst($prize->getExtra()) . " key" . ($prize->getValue() > 1 ? "s" : "");
				case "kp":
					return $prize->getValue() . " " . ucwords(str_replace("-", " ", $prize->getExtra())) . " Key Pack";
				case "tag":
					return $prize->getValue() . " Tag";
				case "module":
					return Prison::getInstance()->getQuests()->getQuest($prize->getValue())->getName() . " Quest Module";
				case "cl":
					return ucwords(str_replace("_", " ", $prize->getValue()));
				default:
					return "";
			}
		}
		return "";
	}

	public function getShortName(int $maxchar = 40) : string{
		$name = $this->getName();
		if(strlen($name) <= $maxchar) return $name;
		return substr($name, 0, $maxchar + 3) . "...";
	}

	public function givePrize(Player $player, bool $title = true, bool $skipFilter = false) : bool{
		/** @var PrisonPlayer $player */
		$prize = $this->getPrize();
		$session = $player->getGameSession()->getMysteryBoxes();

		if(
			!$skipFilter && 
			$session->getFilter()->isEnabled() && 
			$this->getFilterCategory() !== FilterSetting::FILTER_NONE && 
			$session->getFilter()->getSetting($this->getFilterCategory())->getValue() && 
			!$session->getFilter()->isFull($player->getRank())
		){
			if($title) $player->sendTitle($this->getRarityTag(), TextFormat::YELLOW . "(Filtered) " . TextFormat::AQUA.$this->getName(), 10, 30, 10);

			$shopItem = ($prize instanceof Item ? Prison::getInstance()->getShops()->getShopItem($prize) : null);
			$shopPrice = (is_null($shopItem) ? 0 : $shopItem->getSellPrice());

			$session->getFilter(true)->increaseCount(1); // Don't move into the if statement below, its so it can work for Multi Open Keys UI. Line 490 in MysteryBox.php

			if($session->getFilter()->isAutoClearing()){
				$player->getGameSession()->getTechits()->addTechits($shopPrice);
			}else{
				$session->getFilter(true)->addInventoryValue($shopPrice);
			}
			
			return true;
		}
		
		if($title) $player->sendTitle($this->getRarityTag(), TextFormat::AQUA.$this->getName(), 10, 30, 10);

		if($prize instanceof Item){
			if(
				//$prize instanceof RedeemReducer ||
				$prize instanceof CustomDeathTag ||
				$prize instanceof Nametag ||
				$prize instanceof MineNuke
			){
				$prize->init();
			}elseif($prize instanceof RedeemableBook){
				$count = $prize->getCount();
				$prize->setCount(1);
				while($count > 0){
					$newPrize = clone $prize;
					$newPrize->init();
					$player->getInventory()->addItem($newPrize);
					$count--;
				}
				return true;
			}elseif(
				$prize instanceof EssenceOfSuccess || $prize instanceof EssenceOfKnowledge ||
				$prize instanceof EssenceOfProgress || $prize instanceof EssenceOfAscension
			){
				$count = $prize->getCount();
				$prize->setCount(1);
				while($count > 0){
					$newPrize = clone $prize;
					$newPrize->init();
					$player->getInventory()->addItem($newPrize);
					$count--;
				}
				return true;
			}elseif($prize instanceof EffectItem){
				$count = $prize->getCount();
				$prize->setCount(1);
				while($count > 0){
					$newPrize = clone $prize;
					$newPrize->setup(\mt_rand(1, 4));
					$player->getInventory()->addItem($newPrize);
					$count--;
				}
				return true;
			}
			$player->getInventory()->addItem($prize);
			return true;
		}
		if($prize instanceof EffectInstance){
			$player->getEffects()->add($prize);
			return true;
		}
		if($prize instanceof PrizeValFiller){
			switch($prize->getName()){
				case "t":
					$player->addTechits($prize->getValue());
					return true;

				case "k":
					$player->getGameSession()->getMysteryBoxes()->addKeys($prize->getExtra(), $prize->getValue());
					return true;

				case "kp":
					$session = $player->getGameSession()->getMysteryBoxes();
					$pack = KeyPack::PACKS[$prize->getExtra()];
					foreach($pack as $t => $amount)
						$session->addKeys($t, $amount);
					return true;

				case "tag":
					$tags = Prison::getInstance()->getTags();
					$tag = $tags->getTag($prize->getValue());
					$session = $player->getGameSession()->getTags();

					if($session->hasTag($tag)){
						$player->sendMessage(TextFormat::YELLOW . TextFormat::BOLD . "(i) " . TextFormat::RESET . TextFormat::GRAY . "You already have this tag, so you were given " . TextFormat::AQUA . "5000 Techits" . TextFormat::GRAY . " instead.");
						$player->addTechits(5000);
						return true;
					}
					$session->addTag($tag);
					return true;

				case "module":
					$session = $player->getGameSession()->getQuests();
					if(!$session->hasModule($prize->getValue())){
						$session->addModule($prize->getValue());
					}else{
						$player->addTechits(1000);
						$player->sendMessage(TextFormat::YELLOW . TextFormat::BOLD . "(i) " . TextFormat::RESET . TextFormat::GRAY . "You already have this Quest Module, so you were given " . TextFormat::AQUA . "1000 Techits" . TextFormat::GRAY . " instead.");
					}
					return true;

				case "cl":
					$session = $player->getGameSession()->getCells();
					$cl = $prize->getValue();
					if(!$session->hasLayout($cl)){
						$session->addLayout($cl);
						$session->addFloor($cl);
					}else{
						$techits = 25000;
						$player->addTechits($techits);
						$player->sendMessage(TextFormat::YELLOW . TextFormat::BOLD . "(i) " . TextFormat::RESET . TextFormat::GRAY . "You already have this Cell Layout, so you were given " . TextFormat::AQUA . $techits . " Techits" . TextFormat::GRAY . " instead.");
					}
					return true;
				default:
					return false;
			}
		}
		return false;
	}

	public function getFilterCategory() : int{ return $this->filter; }

	public function __toString(): string {
		return "Prize::{" . $this->getName() . ";" . $this->getRarity() . ";" . $this->getPrize()?->getVanillaName() . "}";
	}

}