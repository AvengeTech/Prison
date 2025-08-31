<?php namespace prison\kits;

use pocketmine\player\Player;

use prison\PrisonPlayer;

use core\rank\Structure as RS;
use core\utils\conversion\LegacyItemIds;
use pocketmine\item\VanillaItems;

class Kit{

	public function __construct(
		public string $name,
		
		public array $items = [],
		public array $armor = [],
		
		public int $cooldown = 1,
		public string $rank = "default"
	){}

	public function getName() : string{
		return $this->name;
	}

	public function getItems() : array{
		return $this->items;
	}

	public function getArmor() : array{
		return $this->armor;
	}

	public function getCooldown() : int{
		return $this->cooldown;
	}

	public function getCooldownTime() : int{
		return time() + ($this->cooldown * 60 * 60);
	}

	public function getRank() : string{
		return $this->rank;
	}

	public function hasRequiredRank(Player $player) : bool{
		/** @var PrisonPlayer $player */
		return $player->getRankHierarchy() >= RS::RANK_HIERARCHY[$this->getRank()];
	}

	public function equip(Player $player, bool $cooldown = true) : void{
		/** @var PrisonPlayer $player */
		if($cooldown){
			$session = $player->getGameSession()->getKits();
			$session->setCooldown($this->getName(), $this->getCooldownTime());
		}

		$ai = $player->getArmorInventory();
		foreach($this->getArmor() as $slot => $piece){
			if($ai->getItem($slot)->getTypeId() === VanillaItems::AIR()->getTypeId()){
				$ai->setItem($slot, $piece);
			}else{
				$player->getInventory()->addItem($piece);
			}
		}

		foreach($this->getItems() as $item){
			$player->getInventory()->addItem($item);
		}
	}

}