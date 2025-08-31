<?php

namespace prison\fishing;

use core\utils\ItemRegistry;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\{
	VanillaItems,
};
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;
use prison\enchantments\book\RedeemableBook;
use prison\Prison;
use prison\enchantments\book\RedeemedBook;
use prison\fishing\{
	entity\Hook,
	object\FishingFind,
	object\FindVar
};

class Fishing {

	const RARITY_COMMON = 1;
	const RARITY_UNCOMMON = 2;
	const RARITY_RARE = 3;
	const RARITY_LEGENDARY = 4;

	/** @var FishingFind[] $finds */
	public array $finds = [];

	public function __construct(public Prison $plugin) {
		EntityFactory::getInstance()->register(Hook::class, function (World $world, CompoundTag $nbt): Hook {
			return new Hook(EntityDataHelper::parseLocation($nbt, $world), null);
		}, ["Hook", "minecraft:fishing_hook"]);

		$this->setupFinds();
	}

	public function rerollBooks(): void {
		foreach ($this->finds as $key => $find) {
			$item = $find->getItem();
			if ($item instanceof RedeemableBook) {
				$item->setup(true);
				$find->setItem($item);
			}
		}
		echo "rerolled books!", "\n";
	}

	public function setupFinds(): void {
		/** @var RedeemableBook[] $books */
		$books = [];
		for($rarity = 1; $rarity < 5; $rarity++){
			$book = ItemRegistry::REDEEMABLE_BOOK();
			$book->setup(RedeemableBook::TYPE_RARITY, $rarity);
			$books[] = $book;
		}
		/** @var EffectItem[] $animators */
		$animators = [];
		for($rarity = 1; $rarity < 5; $rarity++){
			$animator = ItemRegistry::ANIMATOR();
			$animator->setup($rarity);
			$animators[] = $animator;
		}

		$this->finds = [
			new FishingFind(VanillaItems::RAW_FISH(), self::RARITY_COMMON, FishingFind::CATEGORY_FISH),
			new FishingFind(VanillaItems::RAW_SALMON(), self::RARITY_UNCOMMON, FishingFind::CATEGORY_FISH),
			new FishingFind(VanillaItems::PUFFERFISH(), self::RARITY_RARE, FishingFind::CATEGORY_FISH),
			new FishingFind(VanillaItems::CLOWNFISH(), self::RARITY_LEGENDARY, FishingFind::CATEGORY_FISH),

			new FishingFind($animators[0], self::RARITY_COMMON, FishingFind::CATEGORY_TREASURE),
			new FishingFind($animators[1], self::RARITY_UNCOMMON, FishingFind::CATEGORY_TREASURE),
			new FishingFind($animators[2], self::RARITY_RARE, FishingFind::CATEGORY_TREASURE),
			new FishingFind($animators[3], self::RARITY_LEGENDARY, FishingFind::CATEGORY_TREASURE),
			new FishingFind(ItemRegistry::MINE_NUKE(), self::RARITY_RARE, FishingFind::CATEGORY_TREASURE),
			new FishingFind(ItemRegistry::HASTE_BOMB(), self::RARITY_LEGENDARY, FishingFind::CATEGORY_TREASURE),
			new FishingFind(VanillaItems::GOLDEN_APPLE(), self::RARITY_RARE, FishingFind::CATEGORY_TREASURE),
			new FishingFind(VanillaItems::ENCHANTED_GOLDEN_APPLE(), self::RARITY_LEGENDARY, FishingFind::CATEGORY_TREASURE),
			new FishingFind(new FindVar("key", 1, ["type" => "iron"]), self::RARITY_COMMON, FishingFind::CATEGORY_TREASURE),
			new FishingFind(new FindVar("key", 1, ["type" => "gold"]), self::RARITY_UNCOMMON, FishingFind::CATEGORY_TREASURE),
			new FishingFind(new FindVar("key", 1, ["type" => "diamond"]), self::RARITY_RARE, FishingFind::CATEGORY_TREASURE),
			new FishingFind(new FindVar("key", 1, ["type" => "emerald"]), self::RARITY_LEGENDARY, FishingFind::CATEGORY_TREASURE),
			new FishingFind($books[0], self::RARITY_COMMON, FishingFind::CATEGORY_TREASURE), // who even wants a common ebook!
			new FishingFind($books[1], self::RARITY_UNCOMMON, FishingFind::CATEGORY_TREASURE),
			new FishingFind($books[2], self::RARITY_RARE, FishingFind::CATEGORY_TREASURE),
			new FishingFind($books[3], self::RARITY_LEGENDARY, FishingFind::CATEGORY_TREASURE),

			new FishingFind(VanillaItems::BOW(), self::RARITY_RARE, FishingFind::CATEGORY_JUNK),
			new FishingFind(ItemRegistry::FISHING_ROD()->setDamage(mt_rand(80, 120)), self::RARITY_UNCOMMON, FishingFind::CATEGORY_JUNK),
			new FishingFind(VanillaItems::LEATHER(), self::RARITY_COMMON, FishingFind::CATEGORY_JUNK),
			new FishingFind(VanillaItems::LEATHER_BOOTS(), self::RARITY_UNCOMMON, FishingFind::CATEGORY_JUNK),
			new FishingFind(VanillaItems::ROTTEN_FLESH(), self::RARITY_COMMON, FishingFind::CATEGORY_JUNK),
			new FishingFind(VanillaItems::STICK(), self::RARITY_COMMON, FishingFind::CATEGORY_JUNK),
			new FishingFind(VanillaItems::STRING(), self::RARITY_COMMON, FishingFind::CATEGORY_JUNK),
			new FishingFind(VanillaItems::BONE(), self::RARITY_COMMON, FishingFind::CATEGORY_JUNK),
			new FishingFind(VanillaItems::FISHING_ROD(), self::RARITY_COMMON, FishingFind::CATEGORY_JUNK),
			new FishingFind(VanillaItems::BOW(), self::RARITY_UNCOMMON, FishingFind::CATEGORY_JUNK),
			new FishingFind(VanillaBlocks::LILY_PAD()->asItem(), self::RARITY_UNCOMMON, FishingFind::CATEGORY_JUNK),
		];
	}

	public function getFinds(int $rarity = -1, $category = -1): array {
		if ($rarity === -1) {
			if ($category == -1) return $this->finds;
			$finds = [];
			foreach ($this->finds as $find) {
				if ($find->getCategory() == $category) {
					$finds[] = $find;
				}
			}
			return $finds;
		}
		$finds = [];
		foreach ($this->finds as $find) {
			if ($find->getRarity() === $rarity && ($category == -1 || $find->getCategory() == $category)) {
				$finds[] = $find;
			}
		}
		return $finds;
	}

	public function getRandomRarity(): int {
		$rarity = 1;
		for ($i = $rarity; $i <= 4; $i++) {
			if (mt_rand(0, $i) == $i) {
				$rarity++;
				continue;
			}
			break;
		}
		return $rarity;
	}

	public function getRandomCategory(): int {
		$category = 0;
		if (mt_rand(0, 5) === 3) {
			$category = (mt_rand(1, 3) === 1 ? 2 : 1);
		}
		return $category;
	}

	public function getRandomFind(int $rarity = -1, int $category = -1): FishingFind {
		$finds = [];
		while (empty($finds)) {
			$finds = $this->getFinds($rarity == -1 ? $this->getRandomRarity() : $rarity, $category);
		}
		return $finds[array_rand($finds)];
	}
}
