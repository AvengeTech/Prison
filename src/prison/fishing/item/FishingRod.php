<?php namespace prison\fishing\item;

use pocketmine\item\{
	Durable,
	ItemUseResult
};
use pocketmine\entity\{
	Entity,
    Living,
    Location
};
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\sound\ThrowSound;

use prison\enchantments\ItemData;
use prison\fishing\entity\Hook;
use prison\PrisonPlayer;

class FishingRod extends Durable{

	public function getMaxStackSize() : int{ return 1; }

	public function getMaxDurability() : int{ return 355; }

	public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems) : ItemUseResult{
		if(!($player instanceof PrisonPlayer)) return ItemUseResult::FAIL();

		if($player->atSpawn() || $player->getGameSession()->getCombat()->isInvincible()){
			return ItemUseResult::FAIL();
		}
		$session = $player->getGameSession()->getFishing();
		if($session->isFishing()){
			if($session->getHook()->isUnderwater()){
				$damage = 5;
			}else{
				$damage = mt_rand(10, 15);
			}
			$this->applyDamage($damage);

			if($session->getHook()->reel($this)){
				$data = new ItemData($this);
				$leveledUp = $data->addCatch();
				$data->apply($this);
				if($leveledUp){
					$data->sendLevelUpTitle($player);
				}
			}
			$session->setFishing();
		}else{
			if($session->isHooked()){
				$this->drag($player, $session->getHooked());
				$session->setHooked();

				$this->applyDamage(mt_rand(5, 10));
			}else{
				$hook = new Hook(Location::fromObject($player->getEyePos(), $player->getWorld(), $player->getLocation()->yaw, $player->getLocation()->pitch), $player, $this);
				$hook->setMotion($player->getDirectionVector()->multiply(0.8));
				$hook->spawnToAll();

				$player->getWorld()->addSound($player->getPosition(), new ThrowSound());
				$session->setFishing($hook);
			}
		}
		return ItemUseResult::SUCCESS();
	}

	public function drag(Entity $to, Entity $from, float $pull = 0.8) : void{
		if(!$from instanceof Living) return;

		$dv = $to->getPosition()->subtract($from->getPosition()->x, $from->getPosition()->y, $from->getPosition()->z)->normalize();
		$from->knockBack($dv->x, $dv->z, $pull);
	}

}