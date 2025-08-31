<?php namespace prison\fishing\object;

use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

use prison\fishing\Fishing;
use prison\PrisonPlayer;

class FishingFind{

	const CATEGORY_FISH = 0;
	const CATEGORY_JUNK = 1;
	const CATEGORY_TREASURE = 2;

	const KEY_COLORS = [
		"iron" => TextFormat::WHITE,
		"gold" => TextFormat::GOLD,
		"diamond" => TextFormat::AQUA,
		"emerald" => TextFormat::GREEN,
		"vote" => TextFormat::YELLOW,
		"divine" => TextFormat::RED,
	];
	const FIND_WORDS = ["Booyah", "Oh yeah", "Woah", "Cha-ching", "Woop", "Sheeesh"];

	public function __construct(
		public Item|FindVar $item,
		public int $rarity,
		public int $category
	){}

	public function getItem() : Item|FindVar{
		return $this->item;
	}

	public function setItem(Item|FindVar $item) : void{
		$this->item = $item;
	}

	public function getRarity() : int{
		return $this->rarity;
	}

	public function getXpDrops() : int{
		switch($this->getCategory()){
			case self::CATEGORY_FISH:
				return match($this->getRarity()){
					Fishing::RARITY_COMMON => mt_rand(0, 2),
					Fishing::RARITY_UNCOMMON => mt_rand(2, 3),
					Fishing::RARITY_RARE => mt_rand(3, 4),
					Fishing::RARITY_LEGENDARY => mt_rand(4, 8),
				};
			case self::CATEGORY_JUNK:
				return match($this->getRarity()){
					Fishing::RARITY_COMMON => 0,
					Fishing::RARITY_UNCOMMON => 0,
					Fishing::RARITY_RARE => mt_rand(0, 3),
					Fishing::RARITY_LEGENDARY => mt_rand(0, 5),
				};
			case self::CATEGORY_TREASURE:
				return match($this->getRarity()){
					Fishing::RARITY_COMMON => mt_rand(0, 2),
					Fishing::RARITY_UNCOMMON => mt_rand(2, 3),
					Fishing::RARITY_RARE => mt_rand(3, 4),
					Fishing::RARITY_LEGENDARY => mt_rand(4, 8),
				};
		}
	}

	public function getCategory() : int{
		return $this->category;
	}

	public function getName() : string{
		return "";
	}

	public function give(Player $player, bool $giveXp = true, int $xpMultiplier = 1) : void{
		if(!($player instanceof PrisonPlayer)) return;

		if(($item = $this->getItem()) instanceof Item){
			$player->getInventory()->addItem($item);
		}elseif($item instanceof FindVar){
			$type = $item->getType();
			switch($type){
				case "key":
					$keytype = $item->getExtra()["type"];
					$player->getGameSession()->getMysteryBoxes()->addKeys($keytype, $item->getAmount());
					$player->sendTitle(TextFormat::YELLOW . self::FIND_WORDS[array_rand(self::FIND_WORDS)], TextFormat::YELLOW . "Found x1 " . self::KEY_COLORS[$keytype] . ucfirst($keytype) . " Key", 10, 40, 10);
					break;
			}
		}
		if($giveXp) $player->getXpManager()->addXp(floor($this->getXpDrops() * $xpMultiplier));
	}

}