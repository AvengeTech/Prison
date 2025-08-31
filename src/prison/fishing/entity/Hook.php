<?php namespace prison\fishing\entity;

use pocketmine\block\Water;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Projectile;
use pocketmine\entity\Entity;
use pocketmine\math\RayTraceResult;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\{
	ActorEventPacket,
	types\ActorEvent,
	types\entity\EntityIds,
};
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

use prison\{
	Prison, 
	PrisonPlayer
};
use prison\entity\ArmorStand;
use prison\fishing\item\FishingRod;
use prison\fishing\event\FishingCatchEvent;
use prison\enchantments\ItemData;
use prison\enchantments\EnchantmentData as ED;

use core\techie\TechieBot;
use core\vote\entity\VoteBox;
use pocketmine\block\Air;
use pocketmine\math\Vector3;
use pocketmine\world\particle\BubbleParticle;
use prison\enchantments\book\RedeemableBook;
use prison\fishing\object\FishingFind;

class Hook extends Projectile{

	private float $width = 0.25;
	private float $height = 0.25;

	protected bool $touchedWater = false;

	private bool $tugging = false;
	private int $tugTime = -1;

	private int $nextTug = -1;

	private ?FishingRod $fishingRod = null;



	public static function getNetworkTypeId() : string{ return EntityIds::FISHING_HOOK; }

	protected function getInitialDragMultiplier(): float{ return 0.02; }

	protected function getInitialGravity(): float{ return 0.05; }

	public function canSaveWithChunk() : bool{ return false; }

	protected function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo($this->height, $this->width);
	}

	public function getName() : string{
		return "Hook";
	}

	public function __construct(Location $loc, ?Entity $shootingEntity = null, ?FishingRod $fishingRod = null){
		parent::__construct($loc, $shootingEntity);
		if(!$shootingEntity instanceof Player){
			$this->flagForDespawn();
			return;
		}
		$this->fishingRod = $fishingRod;

		$this->setNextTug();

		$this->networkPropertiesDirty = true;
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->closed){
			return false;
		}

		$player = $this->getOwningEntity();

		if(!$player instanceof Player){
			$this->flagForDespawn();
			return false;
		}

		if(!$player->getInventory()->getItemInHand() instanceof FishingRod){
			$this->flagForDespawn();
			return false;
		}

		if($player->getPosition()->distance($this->getPosition()) > 30){
			$this->flagForDespawn();
			return false;
		}

		if($this->isUnderwater()){
			$this->motion->x = 0;
			$this->motion->y = 0.07;
			$this->motion->z = 0;

			if(!$this->touchedWater){
				$pk = new ActorEventPacket();
				$pk->actorRuntimeId = $this->getId();
				$pk->eventId = ActorEvent::FISH_HOOK_POSITION;
				NetworkBroadcastUtils::broadcastPackets($this->getViewers(), [$pk]);
			}

			$this->touchedWater = true;
		}elseif($this->isCollided && $this->keepMovement){
			$this->motion->x = 0;
			$this->motion->z = 0;
			$this->keepMovement = false;
		}

		if(!$this->isUnderwater()) return true;

		if($this->isTugging()){
			$this->tickTug();
		}else{
			$this->tickNext();
		}

		return $this->isAlive();
	}

	public function tickTug() : bool{
		$this->tugTime--;

		$player = $this->getOwningEntity();

		if(!$player instanceof Player) return false;

		$position = $this->getPosition();
		$this->getWorld()->addParticle(new Vector3(
			$position->getX() + (mt_rand(-5, 5) * 0.1),
			$this->getWaterHeight(),
			$position->getZ() + (mt_rand(-5, 5) * 0.1)
		), new BubbleParticle());
		
		$player->sendTip(TextFormat::GREEN . "Fish is tugging! " . TextFormat::AQUA . $this->tugTime);
		if($this->tugTime < 0){
			$player->sendTip(TextFormat::RED . "Missed the catch!");
			$this->setTugging(false);
			$this->setNextTug();
			return false;
		}
		return true;
	}

	public function tickNext() : bool{
		$this->nextTug--;

		if($this->nextTug >= 0 && $this->nextTug <= 20){
			$pk = new ActorEventPacket();
			$pk->actorRuntimeId = $this->getId();
			$pk->eventId = ActorEvent::FISH_HOOK_BUBBLE;
			NetworkBroadcastUtils::broadcastPackets($this->getViewers(), [$pk]);
		}

		if($this->nextTug <= 0){
			$this->setTugging();
			$this->nextTug = -1;

			if($this->nextTug === 0){
				$this->motion->y -= 1;
				$pk = new ActorEventPacket();
				$pk->actorRuntimeId = $this->getId();
				$pk->eventId = ActorEvent::FISH_HOOK_TEASE;
				NetworkBroadcastUtils::broadcastPackets($this->getViewers(), [$pk]);
			}
			return false;
		}
		return true;
	}

	public function getTugTime() : int{
		return $this->tugTime;
	}

	public function isTugging() : bool{
		return $this->tugging;
	}

	public function setTugging(bool $bool = true) : void{
		if($bool){
			$pk = new ActorEventPacket();
			$pk->actorRuntimeId = $this->getId();
			$pk->eventId = ActorEvent::FISH_HOOK_HOOK;
			NetworkBroadcastUtils::broadcastPackets($this->getViewers(), [$pk]);

			$tug = 20;
			if($this->fishingRod !== null){
				$fr = $this->fishingRod;
				if($fr->hasEnchantment(EnchantmentIdMap::getInstance()->fromId(ED::SUPER_GLUE))){
					$tug += $fr->getEnchantment(EnchantmentIdMap::getInstance()->fromId(ED::SUPER_GLUE))->getLevel() * 20;
				}
			}
			$this->tugTime = $tug;
		}else{
			$this->tugTime = -1;
		}
		$this->tugging = $bool;
	}

	public function setNextTug() : void{
		$tug = mt_rand(100, 400);
		if($this->fishingRod !== null){
			$ft = $this->fishingRod;
			if($ft->hasEnchantment(EnchantmentIdMap::getInstance()->fromId(ED::POSEIDON))){
				$level = $ft->getEnchantment(EnchantmentIdMap::getInstance()->fromId(ED::POSEIDON))->getLevel();
				switch($level){
					default:
					case 1:
						$tug = mt_rand(60, 300);
						break;
					case 2:
						$tug = mt_rand(50, 170);
						break;
					case 3:
						$tug = mt_rand(40, 140);
						break;
				}
			}
		}
		$this->nextTug = $tug;
	}

	public function reel(?FishingRod $rod = null) : bool{
		$rod = $rod ?? $this->fishingRod;
		if($this->closed){
			return false;
		}

		$player = $this->getOwningEntity();
		if(!$player instanceof PrisonPlayer){
			$this->flagForDespawn();
			return false;
		}

		if(!$this->isUnderwater() && $rod !== null && $rod->hasEnchantment(EnchantmentIdMap::getInstance()->fromId(ED::FLING)) && $player->isSneaking() && $player->isStaff()){
			$rod->drag($this, $player, $rod->getEnchantment(EnchantmentIdMap::getInstance()->fromId(ED::FLING))->getLevel() * 0.8);
			$this->flagForDespawn();
			return false;
		}

		if($this->isTugging()){
			$find = Prison::getInstance()->getFishing()->getRandomFind();
			$item = $find->getItem();

			if($rod !== null){
				$ev = new FishingCatchEvent($player, $find);
				$ev->call();

				if($rod->hasEnchantments()){
					if($rod->hasEnchantment(EnchantmentIdMap::getInstance()->fromId(ED::METAL_DETECTOR))){
						$chances = [
							"iron" => 60,
							"gold" => 120,
							"diamond" => 180,
							"emerald" => 240
						];
						foreach($chances as $type => $chance){
							$found = false;
							if(mt_rand(1, $chance - ($rod->getEnchantment(EnchantmentIdMap::getInstance()->fromId(ED::METAL_DETECTOR))->getLevel() * 5)) == 1){
								$player->getGameSession()->getMysteryBoxes()->addKeys($type);
								$player->sendTitle(TextFormat::YELLOW . FishingFind::FIND_WORDS[array_rand(FishingFind::FIND_WORDS)], TextFormat::YELLOW . "Found x1 " . FishingFind::KEY_COLORS[$type] . ucfirst($type) . " Key", 10, 40, 10);
								$found = true;
								break;
							}
						}
					}
				}

				if($rod !== null){
					$data = new ItemData($rod);
					$chance = ($tl = $data->getTreeLevel(ItemData::SKILL_LOOT)) * 5;
					if(mt_rand(1, 100) <= $chance){
						$find->give($player, false);
						if($tl > 3) $find->give($player, false);
					}
				}

				$find->give($player, true, $rod !== null ? ItemData::SKILL_TREES[ItemData::SKILL_EXP][$data->getTreeLevel(ItemData::SKILL_EXP)] ?? 1 : 1);

				$item = $find->getItem();
				if($item instanceof RedeemableBook){
					Prison::getInstance()->getFishing()->rerollBooks();
				}
				$player->sendTip(TextFormat::GREEN . "Nice catch!");
			}

			$player->getGameSession()->getFishing()->addCatch();
			
			$this->flagForDespawn();
			return true;
		}

		$this->flagForDespawn();
		return false;
	}

	public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult) : void{
		$player = $this->getOwningEntity();
		if(!$player instanceof PrisonPlayer) return;
		if(
			$entityHit instanceof TechieBot ||
			$entityHit instanceof ArmorStand ||
			$entityHit instanceof VoteBox
		) return;

		parent::onHitEntity($entityHit, $hitResult);
		$session = $player->getGameSession()->getFishing();
		$session->setHooked($entityHit);
	}
	
	public function getWaterHeight(): int{
		for($y = $this->getPosition()->getFloorY(); $y < 256; $y++){
			$block = $this->getWorld()->getBlockAt($this->getPosition()->getFloorX(), $y, $this->getPosition()->getFloorZ());
			
			if($block instanceof Air) return $y;
		}
		return $this->getPosition()->getFloorY();
	}
}
