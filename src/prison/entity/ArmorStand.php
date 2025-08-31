<?php namespace prison\entity;

use pocketmine\entity\{
	EntitySizeInfo,
	Living
};
use pocketmine\event\entity\{
	EntityDamageByEntityEvent,
	EntityDamageEvent
};

use pocketmine\item\{Item, Armor, VanillaItems};
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;

use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\player\Player;
use pocketmine\network\mcpe\protocol\{LevelEventPacket, types\entity\EntityIds,
	types\entity\EntityMetadataCollection,
	types\entity\EntityMetadataProperties,
	types\inventory\ItemStackWrapper,
	MobEquipmentPacket,
    PlaySoundPacket,
};
use pocketmine\network\mcpe\protocol\types\inventory\ContainerIds;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\{
	CompoundTag,
	ListTag
};

use prison\PrisonPlayer;

use core\utils\ItemRegistry;
use MyPlot\MyPlot;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use prison\Prison;

class ArmorStand extends Living{

	const CLICK_HELMET = 0;
	const CLICK_CHESTPLATE = 1;
	const CLICK_LEGGINGS = 2;
	const CLICK_BOOTS = 3;

	const DATA_PROPERTY_WOBBLE = 11;

	const TAG_ARMOR_INVENTORY = 'ArmorItems';
	const TAG_HAND_ITEM = 'HandItems';
	const TAG_POSE = 'Pose';

	private Item $itemInHand;
	private int $pose;
	private int $wobbleTicks = 0;
	private bool $can_be_moved_by_currents = true;
	public array $clickDelay = [];



	public static function getNetworkTypeId() : string{ return EntityIds::ARMOR_STAND; }

	public function getInitialSizeInfo() : EntitySizeInfo{
		return new EntitySizeInfo(1.975, 0.5);
	}

	public function getName() : string{
		return 'Armor Stand';
	}

	protected function getInitialGravity() : float{ return 0.04; }

	protected function getInitialDragMultiplier() : float{ return 0.0; }

	protected function syncNetworkData(EntityMetadataCollection $properties) : void{
		parent::syncNetworkData($properties);
		$properties->setInt(EntityMetadataProperties::ARMOR_STAND_POSE_INDEX, $this->pose);
	}

	public function getDrops() : array{
		$drops = $this->getArmorInventory()->getContents();

		if(!($this->itemInHand->isNull())){
			$drops[] = $this->itemInHand;
		}
		
		$drops[] = ItemRegistry::ARMOR_STAND();

		return $drops;
	}

	public function getItemInHand() : Item{
		return $this->itemInHand->setCount(($this->itemInHand->equals(VanillaItems::AIR(), false, false) ? 0 : 1)); // Not sure why but there is a bug that sets items to 0 when placed in stand.
	}

	public function setItemInHand(Item $itemInHand) : void{
		$this->itemInHand = $itemInHand;

		foreach($this->getViewers() as $viewer){
			$viewer->getNetworkSession()->sendDataPacket(MobEquipmentPacket::create(
				$this->getId(), 
				ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($this->getItemInHand())), 
				0, 
				0, 
				ContainerIds::INVENTORY
			));
		}
	}

	public function getPose() : int{
		return $this->pose;
	}

	public function setPose(int $pose) : void{
		$this->pose = $pose;
		$this->networkPropertiesDirty = true;
		$this->scheduleUpdate();
	}

	protected function sendSpawnPacket(Player $player) : void{
		parent::sendSpawnPacket($player);

		$player->getNetworkSession()->sendDataPacket(MobEquipmentPacket::create(
			$this->getId(), 
			ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($this->getItemInHand())), 
			0, 
			0, 
			ContainerIds::INVENTORY
		));
	}

	protected function addAttributes() : void{
		parent::addAttributes();
		$this->setMaxHealth(6);
	}

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);

		$armor_inventory_tag = $nbt->getListTag(self::TAG_ARMOR_INVENTORY);

		if($armor_inventory_tag !== null){
			$armor_inventory = $this->getArmorInventory();
			/** @var CompoundTag $tag */
			foreach($armor_inventory_tag as $tag){
				$armor_inventory->setItem($tag->getByte("Slot"), Item::nbtDeserialize($tag));
			}
		}

		$item_in_hand_tag = $nbt->getCompoundTag(self::TAG_HAND_ITEM);

		$this->itemInHand = ($item_in_hand_tag !== null ? Item::nbtDeserialize($item_in_hand_tag) : VanillaItems::AIR()->setCount(0));

		$this->setPose($nbt->getInt(self::TAG_POSE, 0));
	}

	public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();

		$armor_pieces = [];
		foreach($this->getArmorInventory()->getContents() as $slot => $item){
			$armor_pieces[] = $item->nbtSerialize($slot);
		}
		$nbt->setTag(self::TAG_ARMOR_INVENTORY, new ListTag($armor_pieces, NBT::TAG_Compound));

		if(!$this->itemInHand->isNull()){
			$nbt->setTag(self::TAG_HAND_ITEM, $this->itemInHand->nbtSerialize());
		}

		$nbt->setInt(self::TAG_POSE, $this->pose);
		return $nbt;
	}

	public function knockBack(float $x, float $z, float $force = 0.4, ?float $verticalLimit = 0.4) : void{
	}

	public function actuallyKnockBack(float $x, float $z, float $force = 0.4, ?float $verticalLimit = 0.4) : void{
		parent::knockBack($x, $z, $force, $verticalLimit);
	}

	public function kill() : void{
		parent::kill();

		$this->flagForDespawn();
	}

	public function onInteract(Player $player, Vector3 $clickPos): bool{
		/** @var PrisonPlayer $player */
		if($player->inLegacyPlotWorld()){
			return false;
		}

		$player->getNetworkSession()->getInvManager()->syncContents($player->getInventory());

		if(isset($this->clickDelay[$player->getName()]) && $this->clickDelay[$player->getName()] == time()){
			return false;
		}
		$this->clickDelay[$player->getName()] = time();


		if($player->isSneaking()){
			$pose = $this->pose;
			if(++$pose >= 13){
				$pose = 0;
			}
			$this->setPose($pose);
		}else{
			$diff = $clickPos->getY() - $this->getPosition()->getY();
			switch(true){
				case ($diff < 0.5) :
					$clicked = self::CLICK_BOOTS;
					break;
				case ($diff < 1) :
					$clicked = self::CLICK_LEGGINGS;
					break;
				case ($diff < 1.5) :
					$clicked = self::CLICK_CHESTPLATE;
					break;
				default: // armor stands are only 2-ish blocks tall :shrug:
					$clicked = self::CLICK_HELMET;
					break;
			}

			$standArmorInv = $this->getArmorInventory();
			$standHand = $this->getItemInHand();
			$playerInv = $player->getInventory();
			$playerHand = $playerInv->getItemInHand();
			$equipmentType = $this->getEquipmentSlot($playerHand);
			
			if($playerHand->isNull()){
				if($clicked === self::CLICK_CHESTPLATE){ // Give Chestplate
					if($standHand->isNull() && !$standArmorInv->getChestplate()->isNull()){
						$playerInv->setItemInHand($standArmorInv->getChestplate());
						$standArmorInv->setChestplate(VanillaItems::AIR());
					}elseif(!$standHand->isNull()){ // Give Hand
						$playerInv->setItemInHand($standHand);
						$this->setItemInHand(VanillaItems::AIR());
					}
				}elseif(!$standArmorInv->getItem($clicked)->isNull()){ // Give Other Armor
					$playerInv->setItemInHand($standArmorInv->getItem($clicked));
					$standArmorInv->setItem($clicked, VanillaItems::AIR());
				}
			}elseif(!($playerHand->isNull())){
				if($equipmentType === -1){

					$playerHand2 = clone $playerHand;

					if(!$standHand->isNull() && !$playerHand->isNull()){ // Swap Hands
						if($playerHand->getCount() > 1){
							$playerInv->setItemInHand($playerHand->setCount(($playerHand->getCount() - 1)));
							$playerInv->addItem($standHand);
						}else{
							$playerInv->setItemInHand($standHand);
						}

						$this->setItemInHand($playerHand2->setCount(1));
					}else{ // Set Hand
						$playerInv->setItemInHand(($playerHand->getCount() > 1 ? $playerHand->setCount(($playerHand->getCount() - 1)) : VanillaItems::AIR()));

						$this->setItemInHand($playerHand2->setCount(1));
					}
				}else{
					if($standArmorInv->getItem($equipmentType)->isNull()){ // Set Armor
						$standArmorInv->setItem($equipmentType, $playerHand);
						$playerInv->setItemInHand(VanillaItems::AIR());
					}else{ // Swap Armor
						$playerInv->setItemInHand($standArmorInv->getItem($equipmentType));
						$standArmorInv->setItem($equipmentType, $playerHand);
					}
				}

				$player->getNetworkSession()->getInvManager()->syncContents($player->getInventory());

				NetworkBroadcastUtils::broadcastPackets([$player], [PlaySoundPacket::create(
					"armor.equip_generic",
					$player->getPosition()->getX(),
					$player->getPosition()->getY(),
					$player->getPosition()->getZ(),
					0.75,
					1.0
				)]);
			}
		}

		return true;
	}

	public function getEquipmentSlot(Item $item) : int{
		if($item instanceof Armor){
			$name = $item->getVanillaName();
			switch(true){
				case stripos($name, "helmet"):
				case stripos($name, "cap"):
					return self::CLICK_HELMET;
				case stripos($name, "chestplate"):
				case stripos($name, "tunic"):
					return self::CLICK_CHESTPLATE;
				case stripos($name, "leggings"):
				case stripos($name, "pants"):
					return self::CLICK_LEGGINGS;
				case stripos($name, "boots"):
					return self::CLICK_BOOTS;
			}
		}
		if($item->equals(VanillaBlocks::MOB_HEAD()->asItem(), false, false) || $item->equals(VanillaBlocks::PUMPKIN()->asItem(), false, false)){
			return self::CLICK_HELMET;
		}

		if($item->equals(ItemRegistry::ELYTRA(), false, false)){
			return self::CLICK_CHESTPLATE;
		}

		return -1; // mainhand
	}

	public function attack(EntityDamageEvent $source) : void{
		if($source instanceof EntityDamageByEntityEvent){
			/** @var PrisonPlayer $damager */
			$damager = $source->getDamager();
			if($damager instanceof Player){
				if($damager->inLegacyPlotWorld()){
					$source->cancel();
					return;
				}
				$myplot = MyPlot::getInstance();
				
				$plot = $myplot->getPlotByPosition($this->getPosition());
				if ($plot !== null) {
					$username = $damager->getName();
					$xuid = $damager->getXuid();
					if ($plot->owner == $username || $plot->isHelper((int) $xuid) || $damager->isTier3()) {
						if ($damager->isCreative()) {
							foreach ($this->getViewers() as $viewer) {
								$viewer->getNetworkSession()->sendDataPacket(LevelEventPacket::create(LevelEvent::SOUND_ARMOR_STAND_BREAK, 0, $this->getPosition()));
								$viewer->getNetworkSession()->sendDataPacket(LevelEventPacket::create(LevelEvent::PARTICLE_DESTROY, 5, $this->getPosition()));
							}
							$this->flagForDespawn();
						} else {
							foreach ($this->getViewers() as $viewer) $viewer->getNetworkSession()->sendDataPacket(LevelEventPacket::create(LevelEvent::SOUND_ARMOR_STAND_HIT, 0, $this->getPosition()));
						}
					} else {
						$source->cancel();
					}
				}
			}
		}
		if($source->getCause() != EntityDamageEvent::CAUSE_CONTACT){ // cactus
			parent::attack($source);
		}
	}

	protected function doHitAnimation() : void{
		if(
			$this->lastDamageCause instanceof EntityDamageByEntityEvent &&
			$this->lastDamageCause->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK &&
			$this->lastDamageCause->getDamager() instanceof Player
		){
			$this->wobbleTicks = 9;
		}
	}

	protected function doWobble() : void{
		if($this->wobbleTicks <= 0) return;

		$this->wobbleTicks--;

		$this->getNetworkProperties()->setInt(self::DATA_PROPERTY_WOBBLE, $this->wobbleTicks);
	}

	protected function startDeathAnimation() : void{
	}

	public function canBeMovedByCurrents() : bool{
		return $this->can_be_moved_by_currents;
	}

	public function setCanBeMovedByCurrents(bool $can_be_moved_by_currents) : void{
		$this->can_be_moved_by_currents = $can_be_moved_by_currents;
	}

	protected function entityBaseTick(int $tickDiff = 1) : bool{
		$result = parent::entityBaseTick($tickDiff);

		// $this->doWobble();

		return $result;
	}
}
