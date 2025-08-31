<?php

namespace prison\enchantments\inventory;

use core\AtPlayer;
use core\utils\TextFormat as TF;
use pocketmine\block\inventory\HopperInventory;
use pocketmine\block\tile\Nameable;
use pocketmine\block\tile\Tile;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\SimpleInventory;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use prison\enchantments\uis\conjuror\ConjurorUI;
use prison\item\EssenceOfSuccess;

class RefineEssenceInventory extends HopperInventory{

	private CompoundTag $nbt;

	public function __construct(){
		parent::__construct(new Position(0, 0, 0, Server::getInstance()->getWorldManager()->getWorldByName("newpsn")), 5);
		$this->nbt = CompoundTag::create()->setString(Tile::TAG_ID, "Hopper")->setString(Nameable::TAG_CUSTOM_NAME, $this->getTitle())->setInt(Tile::TAG_X, 0)->setInt(Tile::TAG_Y, 0)->setInt(Tile::TAG_Z, 0);

		$this->setup();
	}

	protected function getTitle() : string{ return "Select Essence To Refine"; }

	public function getDefaultSize() : int{ return 5; }

	public function getNetworkType() : int{ return WindowTypes::HOPPER; }

	public function getNoTouchSlots() : array{ return [0, 1, 3, 4]; }

	public function getIronBarSlots() : array{ return [1, 3]; }

	public function getBackSlot() : int{ return 0; }

	public function getContinueSlot() : int{ return 4; }

	public function getEssenceSlot() : int{ return 2; }

	public function setup() : void{
		foreach($this->getNoTouchSlots() as $slot){
			if($slot === $this->getBackSlot()){
				$this->setItem($slot, VanillaItems::DYE()->setColor(DyeColor::RED())->setCustomName(TF::BOLD . TF::RED . "Back"));
			}elseif($slot === $this->getContinueSlot()){
				$this->setItem($slot, VanillaItems::DYE()->setColor(DyeColor::LIME())->setCustomName(TF::BOLD . TF::GREEN . "Continue"));
			}elseif(in_array($slot, $this->getIronBarSlots())){
				$this->setItem($slot, VanillaBlocks::IRON_BARS()->asItem()->setCustomName(TF::BOLD . TF::GOLD . ($slot === 1 ? "Place Essence -> " : " <- Place Essence")));
			}
		}
	}

	public function onContinue(AtPlayer $player) : void{
		if(($item = $this->getItem($this->getEssenceSlot()))->isNull()){
			foreach($this->getIronBarSlots() as $slot){
				$this->setItem($slot, VanillaBlocks::IRON_BARS()->asItem()->setCustomName(TF::BOLD . TF::RED . "Essence is Missing...")->setLore([
					"",
					TF::GRAY . "You must place essence in",
					TF::GRAY . "the middle slot."
				]));
			}

			$player->playSound("note.bassattack");
			return;
		}

		if(!$item instanceof EssenceOfSuccess){
			foreach($this->getIronBarSlots() as $slot){
				$this->setItem($slot, VanillaBlocks::IRON_BARS()->asItem()->setCustomName(TF::BOLD . TF::RED . "Wrong Item...")->setLore([
					"",
					TF::GRAY . "You must only place essence",
					TF::GRAY . "in the middle slot."
				]));
			}

			$player->playSound("note.bassattack");
			return;
		}

		if($player->getXpManager()->getXpLevel() === $item->getCost()){
			foreach($this->getIronBarSlots() as $slot){
				$this->setItem($slot, VanillaBlocks::IRON_BARS()->asItem()->setCustomName(TF::BOLD . TF::RED . "Not Enough XP...")->setLore([
					"",
					TF::GRAY . "You do not have enough xp",
					TF::GRAY . "levels to continue."
				]));
			}

			$player->playSound("note.bassattack");
			return;
		}

		$this->clear($this->getEssenceSlot());

		$player->showModal(new ConjurorUI($player)); // change to confirm UI
	}

	public function onBack(AtPlayer $player) : void{
		if(!($item = $this->getItem($this->getEssenceSlot()))->isNull()){
			$this->clear($this->getEssenceSlot());

			$player->getInventory()->addItem($item);
		}

		$player->showModal(new ConjurorUI($player));
	}

	public function onOpen(Player $who) : void{
		$id = $who->getNetworkSession()->getInvManager()->getWindowId($this);
		if($id === null) return;

		parent::onOpen($who);
		$pos = new Position($who->getPosition()->getFloorX(), $who->getPosition()->getFloorY() + 2, $who->getPosition()->getFloorZ(), $who->getWorld());

		$this->nbt->setInt(Tile::TAG_X, $pos->x);
		$this->nbt->setInt(Tile::TAG_Y, $pos->y);
		$this->nbt->setInt(Tile::TAG_Z, $pos->z);

		$pk = new UpdateBlockPacket();
		$pk->blockPosition = new BlockPosition($pos->x, $pos->y, $pos->z);
		$pk->blockRuntimeId = TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId(VanillaBlocks::HOPPER()->getStateId());
		$who->getNetworkSession()->sendDataPacket($pk);

		$pk = new BlockActorDataPacket();
		$pk->blockPosition = new BlockPosition($pos->x, $pos->y, $pos->z);
		$pk->nbt = new CacheableNbt($this->nbt);
		$who->getNetworkSession()->sendDataPacket($pk);

		$pk = new ContainerOpenPacket();
		$pk->blockPosition = new BlockPosition($pos->x, $pos->y, $pos->z);
		$pk->windowId = $id;
		$pk->windowType = $this->getNetworkType();
		$who->getNetworkSession()->sendDataPacket($pk);
		$who->getNetworkSession()->getInvManager()->syncContents($this);
	}

	public function onClose(Player $who) : void{
		parent::onClose($who);
		$pos = new Position($this->nbt->getInt(Tile::TAG_X), $this->nbt->getInt(Tile::TAG_Y), $this->nbt->getInt(Tile::TAG_Z), $who->getWorld());

		$this->nbt->setInt(Tile::TAG_X, 0);
		$this->nbt->setInt(Tile::TAG_Y, 0);
		$this->nbt->setInt(Tile::TAG_Z, 0);

		$pk = new UpdateBlockPacket();
		$pk->blockRuntimeId = TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId(VanillaBlocks::HOPPER()->getStateId());
		$pk->blockPosition = new BlockPosition($pos->x, $pos->y, $pos->z);
		$who->getNetworkSession()->sendDataPacket($pk);

		if(!($item = $this->getItem($this->getEssenceSlot()))->isNull()){
			$who->getInventory()->addItem($item);
		}
	}
}