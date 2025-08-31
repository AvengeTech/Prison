<?php namespace prison\item;

use pocketmine\entity\Location;
use pocketmine\entity\projectile\Throwable;
use pocketmine\player\Player;
use pocketmine\item\{
	ItemUseResult,
	ProjectileItem
};
use pocketmine\math\Vector3;
use prison\entity\XpBottle;
use prison\PrisonPlayer;

class ExpBottle extends ProjectileItem{

	public function getThrowForce(): float{
		return 0.6;
	}

	public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems) : ItemUseResult{
		/** @var PrisonPlayer $player */
		if ($player->isSneaking() || $player->getAntiCheatSession()?->isSneaking()) {
			$xp = 0;
			$count = $this->getCount();
			for($i = 1; $i <= $count; $i++){
				$xp += mt_rand(2, 11);
				$this->pop();
			}
			$player->getXpManager()->addXp($xp);
			return ItemUseResult::SUCCESS();
		}else{
			return parent::onClickAir($player, $directionVector, $returnedItems);
		}
	}

	protected function createEntity(Location $location, Player $thrower): Throwable{
		return new XpBottle($location, $thrower);
	}
}