<?php namespace prison\cells;

use prison\Prison;
use prison\cells\commands\{
	CellTeleportCommand,
	CellDoorCommand,
	CellInfoCommand,
	CellLayoutCommand,
	CellFloorCommand,
	CellNumbersCommand,
	CellsCommand
};
use prison\cells\layout\LayoutManager;

class Cells{

	public CellManager $cellManager;
	public LayoutManager $layoutManager;

	public function __construct(public Prison $plugin){
		$this->cellManager = new CellManager($this);
		$this->layoutManager = new LayoutManager($this);

		$plugin->getServer()->getCommandMap()->registerAll("cells", [
			new CellTeleportCommand($plugin, "ctp", "Cell teleport"),
			new CellDoorCommand($plugin, "cd", "Cell door (no touchy)"),
			new CellInfoCommand($plugin, "cin", "Cell info"),
			new CellLayoutCommand($plugin, "cla", "Cell layout"),
			new CellFloorCommand($plugin, "cfl", "Cell floor (no touchy)"),
			new CellNumbersCommand($plugin, "cn", "Cell numbers (no touchy)"),
			new CellsCommand($plugin, "cells", "Cells command"),
		]);
	}

	public function getPlugin() : Prison{
		return $this->plugin;
	}

	public function getCellManager() : CellManager{
		return $this->cellManager;
	}

	public function getLayoutManager() : LayoutManager{
		return $this->layoutManager;
	}

	public function tick() : void{
		$this->getCellManager()->tick();
	}

	public function close() : void{
		$this->getCellManager()->close();
		$this->getLayoutManager()->close();
	}

}
