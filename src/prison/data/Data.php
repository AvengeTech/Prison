<?php namespace prison\data;

use pocketmine\item\{
	ItemFactory
};

use prison\Prison;
use prison\data\commands\{
	AddXp,
};
use prison\data\items\XpNote;

class Data{

	public function __construct(public Prison $plugin){
		$plugin->getServer()->getCommandMap()->register("addxp", new AddXp($plugin, "addxp", "Give a player XP Levels (staff)"));
		//$plugin->getServer()->getCommandMap()->register("xpnote", new XpNoteCommand($plugin, "xpnote", "Put XP Levels into item form!"));
	}

}