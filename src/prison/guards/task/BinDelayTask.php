<?php namespace prison\guards\task;

use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\types\{
	BlockPosition,
	inventory\WindowTypes
};
use pocketmine\scheduler\Task;
use pocketmine\player\Player;
use pocketmine\world\Position;

use prison\guards\inventory\BinInventory;

class BinDelayTask extends Task{

	private $player;
	private $inventory;
	private $position;

	public function __construct(Player $player, BinInventory $inventory, Position $position){
		$this->player = $player;
		$this->inventory = $inventory;
		$this->position = $position;
	}

	public function onRun() : void{
		$pos = $this->position;
		if($this->player->isConnected()){
			$pk = new ContainerOpenPacket();
			$pk->blockPosition = new BlockPosition($pos->x, $pos->y, $pos->z);
			$pk->windowId = $this->player->getNetworkSession()->getInvManager()->getWindowId($this->inventory);
			$pk->windowType = WindowTypes::CONTAINER;
			$this->player->getNetworkSession()->sendDataPacket($pk);
			$this->player->getNetworkSession()->getInvManager()->syncContents($this->inventory);
		}
	}

}