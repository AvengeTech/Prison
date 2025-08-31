<?php namespace prison\skills\events;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;
use prison\skills\Skill;

class SkillEvent extends PlayerEvent implements Cancellable{

	use CancellableTrait;

	public function __construct(
		Player $player, 
		protected Skill $skill
	){
		$this->player = $player;
	}

	public function getSkill() : Skill{
		return $this->skill;
	}
}