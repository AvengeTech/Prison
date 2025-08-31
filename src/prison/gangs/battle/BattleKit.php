<?php namespace prison\gangs\battle;

use pocketmine\player\Player;

use prison\Prison;

use core\utils\conversion\LegacyItemIds;
use pocketmine\item\VanillaItems;

class BattleKit{

	const BATTLE_TAG = "fromBattle";

	public function __construct(
		public string $id,
		public string $name,
		public array $items = [],
		public array $armor = []
	){
		foreach($this->items as $key => $item){
			$nbt = $item->getNamedTag();
			$nbt->setInt(self::BATTLE_TAG, 1);
			$item->setNamedTag($nbt);
			$this->items[$key] = $item;
		}
		foreach($this->armor as $key => $item){
			$nbt = $item->getNamedTag();
			$nbt->setInt(self::BATTLE_TAG, 1);
			$item->setNamedTag($nbt);
			$this->armor[$key] = $item;
		}
	}

	public function getId() : string{
		return $this->id;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getItems() : array{
		return $this->items;
	}

	public function getArmor() : array{
		return $this->armor;
	}

	public function equip(Player $player) : void{
		foreach($this->getItems() as $item){
			$player->getInventory()->addItem($item);
		}

		$ai = $player->getArmorInventory();
		foreach($this->getArmor() as $slot => $piece){
			if($ai->getItem($slot)->getTypeId() === VanillaItems::AIR()->getTypeId()){
				$ai->setItem($slot, $piece);
			}else{
				$player->getInventory()->addItem($piece);
			}
		}
		Prison::getInstance()->getEnchantments()->calculateCache($player);
	}

}