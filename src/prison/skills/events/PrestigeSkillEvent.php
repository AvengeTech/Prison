<?php namespace prison\skills\events;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;
use prison\skills\SkillsComponent;

class PrestigeSkillEvent extends PlayerEvent implements Cancellable{

	use CancellableTrait;

	public function __construct(
		Player $player, 
		private SkillsComponent $component
	){
		$this->player = $player;
	}

	public function getComponent() : SkillsComponent{
		return $this->component;
	}
}