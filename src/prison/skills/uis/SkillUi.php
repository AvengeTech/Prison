<?php namespace prison\skills\uis;

use pocketmine\player\Player;
use core\utils\TextFormat;

use prison\PrisonPlayer;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use prison\skills\Skill;
use prison\skills\SkillsComponent;

class SkillUi extends SimpleForm{

	public function __construct(Player $player, private SkillsComponent $component, string $message = "", bool $error = true, bool $targetIsSender = true){
		/** @var PrisonPlayer $player */
		$axeCombatSkill = $player->getSkill(Skill::SKILL_AXE_COMBAT);
		$combatSkill = $player->getSkill(Skill::SKILL_COMBAT);
		$farmingSkill = $player->getSkill(Skill::SKILL_FARMING);
		$fishingSkill = $player->getSkill(Skill::SKILL_FISHING);
		$miningSkill = $player->getSkill(Skill::SKILL_MINING);
		$prestige = $component->getPrestige();

		parent::__construct(
			$player->getName() . "'s Skills", 
			($message !== "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . "\n" . "\n" : "") .

			"Prestige: " . TextFormat::LIGHT_PURPLE . $prestige . TextFormat::WHITE . "\n" .
			"(All skills must be level " . TextFormat::AQUA . "110" . TextFormat::WHITE . ", when you prestige your skills you earn " . TextFormat::RED . "2 divine keys" . TextFormat::WHITE . ", and add " . TextFormat::AQUA . 10 . TextFormat::WHITE . " levels to all skills per prestige!)" . "\n" . "\n" .
			
			"Axe Combat:" . "\n" .
			"   Level: " . TextFormat::AQUA . $axeCombatSkill->getLevel() . ($axeCombatSkill->getLevel() >= $component->getMaxSkillLevel() ? TextFormat::BOLD . TextFormat::GOLD . " (MAX)" . TextFormat::RESET : TextFormat::WHITE . "\n" .
			"   XP: " . TextFormat::YELLOW . number_format($axeCombatSkill->getExperience()) . TextFormat::GRAY . "/" . TextFormat::GREEN . number_format($axeCombatSkill->experienceNeeded())) . TextFormat::WHITE . "\n" .
			"   Description: " . TextFormat::GRAY . "blah blah" . TextFormat::RED . " shanette " . TextFormat::GRAY . "blah blah" . TextFormat::WHITE . "\n" . "\n" .
			
			"Combat:" . "\n" .
			"   Level: " . TextFormat::AQUA . $combatSkill->getLevel() . ($combatSkill->getLevel() >= $component->getMaxSkillLevel() ? TextFormat::BOLD . TextFormat::GOLD . " (MAX)" . TextFormat::RESET : TextFormat::WHITE . "\n" .
			"   XP: " . TextFormat::YELLOW . number_format($combatSkill->getExperience()) . TextFormat::GRAY . "/" . TextFormat::GREEN . number_format($combatSkill->experienceNeeded())) . TextFormat::WHITE . "\n" .
			"   Description: " . TextFormat::GRAY . "blah blah" . TextFormat::RED . " shanette " . TextFormat::GRAY . "blah blah" . TextFormat::WHITE . "\n" . "\n" .
			
			"Farming:" . "\n" .
			"   Level: " . TextFormat::AQUA . $farmingSkill->getLevel() . ($farmingSkill->getLevel() >= $component->getMaxSkillLevel() ? TextFormat::BOLD . TextFormat::GOLD . " (MAX)" . TextFormat::RESET : TextFormat::WHITE . "\n" .
			"   XP: " . TextFormat::YELLOW . number_format($farmingSkill->getExperience()) . TextFormat::GRAY . "/" . TextFormat::GREEN . number_format($farmingSkill->experienceNeeded())) . TextFormat::WHITE . "\n" .
			"   Description: " . TextFormat::GRAY . "blah blah" . TextFormat::RED . " shanette " . TextFormat::GRAY . "blah blah" . TextFormat::WHITE . "\n" . "\n" .
			
			"Fishing:" . "\n" .
			"   Level: " . TextFormat::AQUA . $fishingSkill->getLevel() . ($fishingSkill->getLevel() >= $component->getMaxSkillLevel() ? TextFormat::BOLD . TextFormat::GOLD . " (MAX)" . TextFormat::RESET : TextFormat::WHITE . "\n" .
			"   XP: " . TextFormat::YELLOW . number_format($fishingSkill->getExperience()) . TextFormat::GRAY . "/" . TextFormat::GREEN . number_format($fishingSkill->experienceNeeded())) . TextFormat::WHITE . "\n" .
			"   Description: " . TextFormat::GRAY . "blah blah" . TextFormat::RED . " shanette " . TextFormat::GRAY . "blah blah" . TextFormat::WHITE . "\n" . "\n" .
			
			"Mining:" . "\n" .
			"   Level: " . TextFormat::AQUA . $miningSkill->getLevel() . ($miningSkill->getLevel() >= $component->getMaxSkillLevel() ? TextFormat::BOLD . TextFormat::GOLD . " (MAX)" . TextFormat::RESET : TextFormat::WHITE . "\n" .
			"   XP: " . TextFormat::YELLOW . number_format($miningSkill->getExperience()) . TextFormat::GRAY . "/" . TextFormat::GREEN . number_format($miningSkill->experienceNeeded())) . TextFormat::WHITE . "\n" .
			"   Description: " . TextFormat::GRAY . "blah blah" . TextFormat::RED . " shanette " . TextFormat::GRAY . "blah blah" . TextFormat::WHITE . "\n" . "\n"
		);

		if($component->canPrestige() && $targetIsSender){
			$this->addButton(new Button("+1 prestige" . "\n" . TextFormat::AQUA . number_format($component->getPrestigeCost()) . " techits"));
		}
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		if($this->component->canPrestige()){
			if($response == 0){
				if($player->getTechits() < $this->component->getPrestigeCost()){
					$player->showModal(new self($player, $this->component, "You don't have enough techits to prestige your skills!"));
					return;
				}
				$player->showModal(new PrestigeSkillsUi($player, $this->component));
				return;
			}
		}
	}
}