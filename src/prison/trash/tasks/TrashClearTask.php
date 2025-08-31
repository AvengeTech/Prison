<?php namespace prison\trash\tasks;

use pocketmine\block\VanillaBlocks;
use pocketmine\scheduler\Task;

use prison\Prison;

class TrashClearTask extends Task{

	public $plugin;
	public $slot;

	public function __construct(Prison $plugin, $slot = 0){
		$this->plugin = $plugin;
		$this->slot = $slot;
	}

	public function onRun() : void{
		$slot = $this->slot;
		foreach($this->plugin->getTrash()->getInventories() as $inventory){
			$prevslot = $slot - 1;
			if($prevslot != -1 && $prevslot != 53){
				$inventory->clear($prevslot);
			}
			if($slot != 53){
				$inventory->setItem($slot, VanillaBlocks::DIRT()->asItem());
			}
		}
		if($slot != 53)
			$this->plugin->getScheduler()->scheduleDelayedTask(new TrashClearTask($this->plugin, $slot + 1), 1);
	}

}