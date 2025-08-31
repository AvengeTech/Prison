<?php namespace prison\enchantments\book;

use core\utils\ItemRegistry;
use pocketmine\player\Player;
use pocketmine\item\Item;
use pocketmine\nbt\{
	NBT,
	tag\ListTag
};

use prison\enchantments\EnchantmentData as ED;

use core\utils\TextFormat as TF;
use pocketmine\nbt\tag\CompoundTag;
use prison\enchantments\type\Enchantment;
use prison\Prison;

class RedeemableBook extends Item{

	protected const TAG_BOOK_RARITY = "rarity";
	protected const TAG_BOOK_TYPE = "type";
	protected const TAG_INIT = "init";
	protected const TAG_REDEEM_COST = "redeemcost";

	// s1 books
	protected const TAG_SUCCESS_RATE = "success_rate";
	protected const TAG_SUCCESS_ENCHANTMENT = "success_enchantment";
	protected const TAG_FAIL_RATE = "fail_rate";
	protected const TAG_FAIL_ENCHANTMENT = "fail_enchantment";

	const TYPE_NORMAL = 0;
	const TYPE_RARITY = 1;
	const TYPE_MAX_RARITY = 2;
	const TYPE_RANDOM_RARITY = 3;
	const TYPE_MAX_RANDOM_RARITY = 4;

	private int $rarity = 1;
	private int $type = 1;
	private bool $includeDivine = false;

	private int $successRate = 0;
	private int $failRate = 0;
	private ?Enchantment $successEnchantment = null;
	private ?Enchantment $failEnchantment = null;

	public function setup(int $type, int $rarity = -1, bool $includeDivine = false): self {
		$this->rarity = $rarity;
		$this->type = $type;
		$this->includeDivine = $includeDivine;
		return $this->init();
	}

	public function init() : self{
		$this->setCustomName($this->getBookName());

		$this->getNamedTag()->setByte(self::TAG_INIT, 1);

		$this->getNamedTag()->setLong("item_random_id", (int) (time() * mt_rand(-999, 999)));

		$lores = [];

		switch($this->getType()){
			case self::TYPE_MAX_RANDOM_RARITY:
				$lores[] = TF::GRAY . "a random max level enchantment";
				$lores[] = TF::GRAY . "from any rarity";
				break;
			
			case self::TYPE_MAX_RARITY;
				$lores[] = TF::GRAY . "a random max level enchantment";
				$lores[] = TF::GRAY . "from the " . $this->getRarityName() . TF::GRAY . " rarity";
				break;
			
			case self::TYPE_RANDOM_RARITY:
				$lores[] = TF::GRAY . "a random enchantment of any level";
				$lores[] = TF::GRAY . "from any rarity";
				break;

			case self::TYPE_RARITY:
				$lores[] = TF::GRAY . "a random enchantment of any level";
				$lores[] = TF::GRAY . "from the " . $this->getRarityName() . TF::GRAY . " rarity";
				break;

			// S1 Book without XP cost
			case self::TYPE_NORMAL:
				$this->generate();

				$lores[] = TF::GREEN . $this->successRate . "%" . TF::WHITE . " success rate";
				$lores[] = TF::RED . $this->failRate . "%" . TF::WHITE . " fallover rate";
				$lores[] = " ";
				$lores[] = TF::GREEN . "Success Enchantment:";
				$lores[] = "  " . $this->successEnchantment->getLore($this->successEnchantment->getStoredLevel());
				$lores[] = " ";
				$lores[] = TF::RED . "Fallover Enchantment:";
				$lores[] = "  " . $this->failEnchantment->getLore($this->failEnchantment->getStoredLevel());
				$lores[] = " ";
				$lores[] = TF::GRAY . "Right-Click to redeem this book! You";
				$lores[] = TF::GRAY . "have a chance to either receive";
				$lores[] = TF::GRAY . "the " . TF::GREEN . "Success Enchantment";
				$lores[] = TF::GRAY . "or the " . TF::RED . "Fallover Enchantment";
				break;
		}
		
		if($this->type !== self::TYPE_NORMAL){
			$lores[] = " ";
			$lores[] = TF::GRAY . "Right-Click this book to receive";
		}

		if($this->includeDivine && $this->rarity !== ED::RARITY_DIVINE){
			$lores[] = " ";
			$lores[] = TF::GRAY . "(Includes " . TF::RED . TF::BOLD . "Divine" . TF::RESET . TF::GRAY . " Enchantments)";
		}
		
		foreach($lores as $key => $lore) $lores[$key] = TF::RESET . $lore;

		$this->setLore($lores);

		$this->getNamedTag()->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));
		return $this;
	}

	public function redeem(Player $player) : void{
		$enchantment = null;

		switch($this->getType()){
			case self::TYPE_MAX_RANDOM_RARITY:
				$chance = mt_rand(1, 100);

				if($this->includeDivine){
					if($chance <= 96){
						$rarity = mt_rand(1, 4);
					}else{
						$rarity = ED::RARITY_DIVINE;
					}
				}else{
					if($chance <= 35){
						$rarity = ED::RARITY_COMMON;
					}elseif($chance >= 36 && $chance <= 65){
						$rarity = ED::RARITY_UNCOMMON;
					}elseif($chance >= 66 && $chance <= 85){
						$rarity = ED::RARITY_RARE;
					}else{
						$rarity = ED::RARITY_LEGENDARY;
					}
				}

				$enchantment = Prison::getInstance()->getEnchantments()->getRandomEnchantment($rarity);
				$enchantment->setStoredLevel($enchantment->getMaxLevel());
				break;
			
			case self::TYPE_MAX_RARITY;
				$enchantment = Prison::getInstance()->getEnchantments()->getRandomEnchantment($this->getRarity());
				$enchantment->setStoredLevel($enchantment->getMaxLevel());
				break;

			case self::TYPE_RANDOM_RARITY:
				$chance = mt_rand(1, 100);

				if($this->includeDivine){
					if($chance <= 99){
						$rarity = mt_rand(1, 4);
					}else{
						$rarity = ED::RARITY_DIVINE;
					}
				}else{
					if($chance <= 35){
						$rarity = ED::RARITY_COMMON;
					}elseif($chance >= 36 && $chance <= 65){
						$rarity = ED::RARITY_UNCOMMON;
					}elseif($chance >= 66 && $chance <= 85){
						$rarity = ED::RARITY_RARE;
					}else{
						$rarity = ED::RARITY_LEGENDARY;
					}
				}

				$enchantment = Prison::getInstance()->getEnchantments()->getRandomEnchantment($rarity);
				$chance = mt_rand(1, 100);
				switch(true){
					case $chance <= 40:
						$level = 1;
						break;
					
					case $chance <= 30:
						$level = 2;
						break;

					case $chance <= 15:
						$level = 3;
						break;

					case $chance <= 10:
						$level = 4;
						break;

					case $chance <= 5:
						$level = 5;
						break;

					default:
						$level = 1;
				}

				$enchantment->setStoredLevel(min($level, $enchantment->getMaxLevel()));
				break;

			case self::TYPE_RARITY:
				$enchantment = Prison::getInstance()->getEnchantments()->getRandomEnchantment($this->getRarity());

				$chance = mt_rand(1, 100);
				switch(true){
					case $chance <= 40:
						$level = 1;
						break;
					
					case $chance <= 30:
						$level = 2;
						break;

					case $chance <= 15:
						$level = 3;
						break;

					case $chance <= 10:
						$level = 4;
						break;

					case $chance <= 5:
						$level = 5;
						break;

					default:
						$level = 1;
				}

				$enchantment->setStoredLevel(min($level, $enchantment->getMaxLevel()));
				break;
				
			case self::TYPE_NORMAL:
				$enchantment = (($success = mt_rand(1, 100) <= $this->successRate) ? $this->successEnchantment : $this->failEnchantment);
				break;
		}

		if(is_null($enchantment)) return;

		$book = ItemRegistry::REDEEMED_BOOK();
		$book->setup($enchantment);

		$player->getInventory()->setItemInHand($book);

		if($this->type === self::TYPE_NORMAL){
			$msg = TF::YN . "Successfully redeemed your enchantment book! Received the ";
			if($success){
				$msg .= TF::GREEN . "Success enchantment";
			}else{
				$msg .= TF::RED . "Fallover enchantment";
			}
			
			$player->sendMessage($msg);
			return;
		}
		
		$player->sendMessage(TF::YN . "Successfully redeemed your enchantment book, you got " . $book->getEnchant()->getLore($book->getEnchant()->getStoredLevel()));
	}

	public function getMaxStackSize() : int{ return 1;}

	public function getRarity() : int{ return $this->rarity; }

	public function setRarity(int $rarity) : self{
		$this->rarity = $rarity;
		return $this;
	}

	public function getType() : int{ return $this->type; }

	public function isInitiated() : bool{ return (bool) $this->getNamedTag()->getByte(self::TAG_INIT, 0); }

	public function getRarityName(int $rarity = -1) : string{
		if($rarity === -1) $rarity = $this->getRarity();

		switch($rarity){
			case ED::RARITY_COMMON:
				return TF::RESET . TF::GREEN . "Common";
			case ED::RARITY_UNCOMMON:
				return TF::RESET . TF::DARK_GREEN . "Uncommon";
			case ED::RARITY_RARE:
				return TF::RESET . TF::YELLOW . "Rare";
			case ED::RARITY_LEGENDARY:
				return TF::RESET . TF::GOLD . "Legendary";
			case ED::RARITY_DIVINE:
				return TF::RESET . TF::RED . "Divine";
		}

		return ' ';
	}

	public function getBookName(int $rarity = -1, int $type = -1) : string{
		if($rarity === -1) $rarity = $this->getRarity();
		if($type === -1) $type = $this->getType();

		$name = $this->getRarityName($rarity) . ' ';
		$randomBook = TF::BOLD . TF::GREEN.'R'.TF::DARK_GREEN.'A'.TF::YELLOW.'N'.TF::GOLD.'D'.TF::RED .'O'.TF::RED.'M'.' '.TF::GOLD.'B'.TF::YELLOW.'O'.TF::DARK_GREEN.'O'.TF::GREEN . 'K';
		
		switch($type){
			case self::TYPE_MAX_RANDOM_RARITY:
				$name = TF::DARK_PURPLE . 'Max ' . $randomBook;
				break;
			case self::TYPE_MAX_RARITY:
				$name .= "Max Book";
				break;
			case self::TYPE_RANDOM_RARITY:
				$name = $randomBook;
				break;
			case self::TYPE_NORMAL:
			case self::TYPE_RARITY:
				$name .= "Book";
				break;
		}

		$name .= TF::RESET . TF::GRAY;

		return $name;
	}

	public function getSuccessEnchantment() : ?Enchantment{
		return $this->successEnchantment;
	}

	public function getFailEnchantment() : ?Enchantment{
		return $this->failEnchantment;
	}

	public function getSuccessRate() : int{
		return $this->successRate;
	}

	public function getFailRate() : int{
		return $this->failRate;
	}

	/**
	 * DOES NOT WORK PROPERLY
	 * 
	 * DUPLICATES BOOKS FOR SOME REASON!
	 */
	public function generate() : void{
		if($this->rarity === ED::RARITY_DIVINE){
			$enchants = Prison::getInstance()->getEnchantments()->getEnchantments($this->rarity);
		}else{
			$enchants = array_merge(
				($this->rarity != ED::RARITY_COMMON ? Prison::getInstance()->getEnchantments()->getEnchantments($this->rarity - 1) : []),
				Prison::getInstance()->getEnchantments()->getEnchantments($this->rarity)
			);
		}

		shuffle($enchants);
		
		foreach($enchants as $key => $enchant){
			$enchant->setStoredLevel(mt_rand(1, $enchant->getMaxLevel()));
			$enchants[$key] = $enchant;
		}

		foreach($enchants as $key => $enchant){
			if($enchant->getRarity() === $this->rarity){
				unset($enchants[$key]);
				$this->successEnchantment = $enchant->setStoredLevel(mt_rand(1, $enchant->getMaxLevel()));
				break;
			}
		}

		shuffle($enchants);
		
		foreach($enchants as $enchant){
			if($enchant->getRarity() <= $this->rarity){
				unset($enchants[$key]);
				$this->failEnchantment = $enchant;
				break;
			}
		}
		$this->successRate = mt_rand(20, 80);
		$this->failRate = 100 - $this->successRate;
	}

	protected function deserializeCompoundTag(CompoundTag $tag) : void{
		parent::deserializeCompoundTag($tag);

		$this->rarity = $tag->getInt(self::TAG_BOOK_RARITY, 1);
		$this->type = $tag->getInt(self::TAG_BOOK_TYPE, 1);

		if($this->type === self::TYPE_NORMAL && $tag->getByte("init", 0) == 1){
			$this->successRate = $tag->getInt(self::TAG_SUCCESS_RATE, 50);
			$this->failRate = $tag->getInt(self::TAG_FAIL_RATE, 50);

			$successData = $tag->getIntArray(self::TAG_SUCCESS_ENCHANTMENT, []);
			$failData = $tag->getIntArray(self::TAG_FAIL_ENCHANTMENT, []);

			$this->successEnchantment = Prison::getInstance()->getEnchantments()->getEnchantment($successData[0])->setStoredLevel($successData[1]);
			$this->failEnchantment = Prison::getInstance()->getEnchantments()->getEnchantment($failData[0])->setStoredLevel($failData[1]);
		}
	}

	protected function serializeCompoundTag(CompoundTag $tag) : void{
		parent::serializeCompoundTag($tag);

		if($tag->getByte("init", 0) == 1)
			$tag->setTag(Item::TAG_ENCH, new ListTag([], NBT::TAG_Compound));

		$tag->setInt(self::TAG_BOOK_RARITY, $this->rarity);
		$tag->setInt(self::TAG_BOOK_TYPE, $this->type);

		if($this->type === self::TYPE_NORMAL && $tag->getByte("init", 0) == 1){
			$tag->setInt(self::TAG_SUCCESS_RATE, $this->successRate);
			$tag->setInt(self::TAG_FAIL_RATE, $this->failRate);
			$tag->setIntArray(self::TAG_SUCCESS_ENCHANTMENT, [$this->successEnchantment->getId(), $this->successEnchantment->getStoredLevel()]);
			$tag->setIntArray(self::TAG_FAIL_ENCHANTMENT, [$this->failEnchantment->getId(), $this->failEnchantment->getStoredLevel()]);
		}
	}
}