<?php namespace prison\vaults\tasks;

use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\types\{
	BlockPosition,
	inventory\WindowTypes
};
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;

use prison\vaults\inventory\VaultInventory;

class VaultDelayTask extends Task{

	public function __construct(
		public Player $player,
		public VaultInventory $inventory,
		public Position $pos
	){}

	public function onRun() : void{
		$pos = $this->pos;
		if($this->player->isConnected()){
			$pk = new ContainerOpenPacket();
			$pk->blockPosition = new BlockPosition($pos->x, $pos->y, $pos->z);
			$pk->windowId = (int) $this->player->getNetworkSession()->getInvManager()->getWindowId($this->inventory);
			$pk->windowType = WindowTypes::CONTAINER;

			$this->player->getNetworkSession()->sendDataPacket($pk);
			$this->player->getNetworkSession()->getInvManager()->syncContents($this->inventory);
		}
	}

}