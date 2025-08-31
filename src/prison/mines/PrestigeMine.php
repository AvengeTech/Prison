<?php namespace prison\mines;

use pocketmine\math\Vector3;
use pocketmine\world\World as Level;

class PrestigeMine extends Mine{

	public $prestige = 1;

	public function __construct(string $name, int $reset, Vector3 $spawn, Vector3 $corner1, Vector3 $corner2, Level $level, array $blocks, int $prestige = 1){
		parent::__construct($name, $reset, $spawn, $corner1, $corner2, $level, $blocks);
		$this->prestige = $prestige;
	}

	public function getDisplayName() : string{
		return "Prestige " . $this->getRequiredPrestige() . " Mine";
	}

	public function getRequiredPrestige() : int{
		return $this->prestige;
	}

}