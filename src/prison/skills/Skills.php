<?php namespace prison\skills;

use pocketmine\item\{
	Armor,
	Axe,
	Hoe,
	Item,
	Pickaxe,
    Shovel,
    Sword,
	TieredTool,
	ToolTier as TTier,
	VanillaArmorMaterials as ATier
};
use prison\Prison;
use prison\skills\commands\{
	SetSkillCommand,
	SkillsCommand
};

class Skills{

	public function __construct(
		private Prison $plugin
	){
		$plugin->getServer()->getCommandMap()->registerAll('skills', [
			new SetSkillCommand($plugin, "setskill", "set the level of a player's skill"), 
			new SkillsCommand($plugin, "skills", "check out your skills")
		]);
	}

	public function canUseItem(Item $item, int $level, bool $exactTool = false, string $skill = '') : bool{
		$materials = [
			0 => [ATier::LEATHER()],
			1 => [ATier::LEATHER(), ATier::CHAINMAIL()],
			2 => [ATier::LEATHER(), ATier::CHAINMAIL(), ATier::GOLD()],
			3 => [ATier::LEATHER(), ATier::CHAINMAIL(), ATier::GOLD(), ATier::IRON()],
			4 => [ATier::LEATHER(), ATier::CHAINMAIL(), ATier::GOLD(), ATier::IRON(), ATier::DIAMOND()],
			5 => [ATier::LEATHER(), ATier::CHAINMAIL(), ATier::GOLD(), ATier::IRON(), ATier::DIAMOND(), ATier::NETHERITE()]
		];
		$tiers = [
			0 => [TTier::WOOD()],
			1 => [TTier::WOOD(), TTier::STONE()],
			2 => [TTier::WOOD(), TTier::STONE(), TTier::GOLD()],
			3 => [TTier::WOOD(), TTier::STONE(), TTier::GOLD(), TTier::IRON()],
			4 => [TTier::WOOD(), TTier::STONE(), TTier::GOLD(), TTier::IRON(), TTier::DIAMOND()],
			5 => [TTier::WOOD(), TTier::STONE(), TTier::GOLD(), TTier::IRON(), TTier::DIAMOND(), TTier::NETHERITE()]
		];

		$matches = [];

		if($item instanceof Armor && $skill === Skill::SKILL_COMBAT){
			$matches = $materials[(int) min(5, round($level / 20, PHP_ROUND_HALF_DOWN))];
		}elseif(
			$exactTool && (
				$item instanceof Sword && $skill === Skill::SKILL_COMBAT || 
				$item instanceof Axe && $skill === Skill::SKILL_AXE_COMBAT || 
				($item instanceof Pickaxe || $item instanceof Shovel) && $skill === Skill::SKILL_MINING || 
				$item instanceof Hoe && $skill === Skill::SKILL_FARMING
			) ||
			!($exactTool) && $item instanceof TieredTool
		){
			$matches = $tiers[(int) min(5, round($level / 20, PHP_ROUND_HALF_DOWN))];
		}

		if(empty($matches)) return true;

		foreach($matches as $match){
			if(
				$item instanceof Armor && $item->getMaterial() === $match ||
				$item instanceof TieredTool && $item->getTier() === $match
			) return true;
		}

		return false;
	}

	public function tick() : void{

	}
}