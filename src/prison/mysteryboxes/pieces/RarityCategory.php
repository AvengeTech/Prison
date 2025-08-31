<?php namespace prison\mysteryboxes\pieces;

use core\utils\ItemRegistry;
use core\utils\Utils;
use pocketmine\block\Block;
use pocketmine\block\Concrete;
use pocketmine\block\ConcretePowder;
use pocketmine\block\Coral;
use pocketmine\block\CoralBlock;
use pocketmine\block\FloorCoralFan;
use pocketmine\block\StainedGlass;
use pocketmine\block\utils\CoralType;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\Wool;
use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\item\Dye;
use pocketmine\item\Item;

use prison\enchantments\book\RedeemableBook;
use prison\enchantments\EnchantmentData;
use prison\Prison;
use prison\item\{
    EssenceOfAscension,
    EssenceOfKnowledge,
    EssenceOfProgress,
    EssenceOfSuccess,
	HasteBomb,
	MineNuke,
	UnboundTome
};
use prison\mysteryboxes\Structure;

class RarityCategory{

	/** @param Prize[] $prizes */
	private array $prizes = [];

	public function __construct(
		private int $id
	){
		$rarity = $id;

		foreach(Structure::PRIZES_NEW[$id] as $pdata => $extraData){
			$data = explode(":", $pdata);

			$type = array_shift($data);
			switch($type){
				case "i":
					$id = array_shift($data);
					$count = (count($data) == 0 ? 1 : array_shift($data));

					$prize = ItemRegistry::findItem($id)?->setCount($count);

					if (is_null($prize)) break;

					if($prize instanceof HasteBomb){
						$prize->init();
					}elseif($prize instanceof MineNuke){
						$prize->init();
					}elseif($prize instanceof UnboundTome){
						$prize->init((int)(count($data) == 0 ? 1 : array_shift($data)));
					}elseif($prize instanceof RedeemableBook){ // include divine
						$prize->setup(
							(int)(count($data) == 0 ? 1 : array_shift($data)),
							(int)(count($data) == 0 ? -1 : array_shift($data)),
							(bool)(count($data) == 0 ? false : array_shift($data))
						);
					}elseif(
						$prize instanceof EssenceOfSuccess || $prize instanceof EssenceOfKnowledge ||
						$prize instanceof EssenceOfProgress || $prize instanceof EssenceOfAscension
					){
						$prize->setup((int)(count($data) == 0 ? 1 : array_shift($data)));
					}
					if(!empty($data)){
						$name = array_shift($data);
						if($name != "x"){
							$prize->setCustomName($name);
						}
					}
					//"1:0:1:Enchanted Item:This,Is,A,Custom,Description:1;1,2;1,3;1
					break;
				case "e":
					$id = array_shift($data);
					$level = array_shift($data) - 1;
					$duration = array_shift($data) * 20;
					$prize = new EffectInstance(EffectIdMap::getInstance()->fromId($id), $duration, $level);
					break;
				case "pvf":
					$name = array_shift($data);
					$value = array_shift($data);
					$prize = new PrizeValFiller($name, $value);
					if(!empty($data)){
						$prize->setExtra(array_shift($data));
					}
					break;
				case "module":
					$value = array_shift($data);
					$prize = new PrizeValFiller("module", $value);
					break;
				default:
					break;
			}
			if (is_null($prize)) {
				Utils::dumpVals($pdata . " || INVALID ITEM");
				continue;
			}

			$this->prizes[$extraData["subRarity"]][] = new Prize($prize, $rarity, $extraData["filter"]);
		}
	}

	public function getId() : int{ return $this->id; }

	public function getRarity() : int{ return $this->getId(); }

	public function getPrizes() : array{ return $this->prizes; }

	public function getRandomPrize() : ?Prize{
		$subRarity = (($chance = mt_rand(1, 100)) <= 50 ? 0 : ($chance <= 80 ? 1 : ($chance <= 95 ? 2 : 3)));

		if($this->id === (EnchantmentData::RARITY_LEGENDARY - 1) && $subRarity === 3){
			$subRarity = (mt_rand(1, 100) <= 90 ? 3 : 4);
		}

		$prize = clone $this->prizes[$subRarity][array_rand($this->prizes[$subRarity])];

		$pitem = $prize->getPrize();
		if($pitem instanceof PrizeValFiller){
			if($pitem->getName() == "tag"){
				$prize = clone $prize;
				$pp = $prize->getPrize();
				$pp->setValue(Prison::getInstance()->getTags()->getRandomTag()->getName());
				$prize->prize = $pp;
			}elseif($pitem->getName() == "module"){
				$prize = clone $prize;
				$prize->getPrize()->setValue(mt_rand(0, 5));
			}
			if($pitem->getName() == "cl"){
				$lm = Prison::getInstance()->getCells()->getLayoutManager();
				$prize = clone $prize;
				$prize->getPrize()->setValue($lm->getRandomLayout($lm->getLayouts(false))->getName());
			}
		}

		if($pitem instanceof Item){
			if($pitem instanceof Dye){
				$pitem->setColor(DyeColor::getAll()[array_rand(DyeColor::getAll())]);
				$prize->prize = $pitem;
			}
			if(($pBlock = $pitem->getBlock()) instanceof Block){
				if($pBlock instanceof Concrete || $pBlock instanceof ConcretePowder || $pBlock instanceof Wool || $pBlock instanceof StainedGlass){
					$pBlock->setColor(DyeColor::getAll()[array_rand(DyeColor::getAll())]);
					$prize->prize = $pBlock->asItem()->setCount($pitem->getCount());
				}
				if($pBlock instanceof CoralBlock || $pBlock instanceof FloorCoralFan || $pBlock instanceof Coral){
					$pBlock->setCoralType(CoralType::getAll()[array_rand(CoralType::getAll())]);
					$prize->prize = $pBlock->asItem()->setCount($pitem->getCount());
				}
			}
		}

		return $prize;
	}
}