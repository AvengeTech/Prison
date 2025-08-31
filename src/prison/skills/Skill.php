<?php namespace prison\skills;

class Skill{

	const SKILL_MINING = 'mining';
	const SKILL_COMBAT = 'combat';
	const SKILL_FARMING = 'farming';
	const SKILL_AXE_COMBAT = 'axe_combat';
	const SKILL_FISHING = 'fishing';

	const XP_PER_FIVE_LEVELS = 500;
	const XP_PER_LEVEL = 1250;
	const XP_PER_PRESTIGE = 2500;

	public function __construct(
		private string $identifier,
		private int $level,
		private int $experience,
		private SkillsComponent $component
	){}


	public function getIdentifier() : string{
		return $this->identifier;
	}

	public function getLevel() : int{
		return $this->level;
	}

	public function getExperience() : int{
		return $this->experience;
	}

	public function addLevel(int $level) : void{
		$this->setLevel($this->getLevel() + $level);
	}

	public function reduceLevel(int $level) : void{
		$this->setLevel($this->getLevel() - $level);
	}

	public function setLevel(int $level) : void{
		$this->level = $level;

		$this->component->setChanged();
	}

	public function addExperience(int $experience) : void{
		$this->setExperience($this->getExperience() + $experience);
	}

	public function reduceExperience(int $experience) : void{
		$this->setExperience($this->getExperience() - $experience);
	}

	public function setExperience(int $experience) : void{
		$this->experience = $experience;

		$this->component->setChanged();
	}

	public function experienceNeeded() : int{
		$needed = ($this->level * self::XP_PER_LEVEL) + ($this->component->getPrestige() * self::XP_PER_PRESTIGE);

		if($this->level >= 5){
			$needed += round($this->level / 5, PHP_ROUND_HALF_DOWN) * self::XP_PER_FIVE_LEVELS;
		}


		return $needed;
	}

	public function canLevelUp() : bool{
		return (
			$this->experience == $this->experienceNeeded() && 
			$this->level !== $this->component->getMaxSkillLevel()
		);
	}

	public function levelUp(Skill $skill) : void{
		if(!$this->canLevelUp($skill)) return;

		$this->setExperience(max(0, ($this->experience - $this->experienceNeeded())));
		$this->addLevel(1);

		$this->component->setChanged();
	}
}