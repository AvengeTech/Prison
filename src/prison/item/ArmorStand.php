<?php namespace prison\item;

use pocketmine\block\Block;
use pocketmine\entity\{
	Location
};
use pocketmine\item\{
	ItemUseResult
};
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\{
	LevelEventPacket,
	types\LevelEvent
};
use pocketmine\player\Player;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;

use prison\entity\ArmorStand as EntityArmorStand;
use prison\PrisonPlayer;

class ArmorStand extends Item{

	public function onInteractBlock(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, array &$returnedItems) : ItemUseResult{
		/** @var PrisonPlayer $player */
		if(!$player->inPlotWorld()) return ItemUseResult::FAIL();

		$vec = $blockReplace->getPosition()->asVector3()->add(0.5, 0, 0.5);
		$entity = new EntityArmorStand(new Location($vec->x, $vec->y, $vec->z, $player->getWorld(), $this->getDirection($player->getLocation()->getYaw()), 0), new CompoundTag());

		if($entity instanceof EntityArmorStand){
			if($player->isSurvival()){
				$this->count--;
				$player->getInventory()->setItemInHand($this);
			}

			$entity->spawnToAll();
			foreach($player->getViewers() as $viewer){
				$viewer->getNetworkSession()->sendDataPacket(LevelEventPacket::create(LevelEvent::SOUND_ARMOR_STAND_PLACE, 0, $entity->getPosition()));
			}
			return ItemUseResult::SUCCESS();
		}

		return ItemUseResult::FAIL();
	}

	public function getDirection(float $yaw){
		return (round($yaw / 22.5 / 2) * 45) - 180;
	}

}