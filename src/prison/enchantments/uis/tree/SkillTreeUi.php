<?php namespace prison\enchantments\uis\tree;

use pocketmine\item\{
	Sword,
	Pickaxe
};
use pocketmine\player\Player;

use prison\enchantments\ItemData;
//use skyblock\fishing\item\FishingRod;

use core\ui\elements\simpleForm\Button;
use core\ui\windows\SimpleForm;
use core\utils\TextFormat;
use prison\PrisonPlayer;

class SkillTreeUi extends SimpleForm{

	const LOOTING_DATA_SWORD = [
		1 => "5%% chance of double item drops from mobs",
		2 => "10%% chance of double item drops from mobs",
		3 => "15%% chance of double item drops from mobs",
		4 => "20%% chance of triple item drops from mobs",
		5 => "25%% chance of triple item drops from mobs",
	];

	const EXP_DATA_SWORD = [
		1 => "x1.5 exp drops from mobs",
		2 => "x2 exp drops from mobs",
		3 => "x2.5 exp drops from mobs",
		4 => "x3 exp drops from mobs",
		5 => "x4 exp drops from mobs",
	];

	const LOOTING_DATA_PICKAXE = [
		1 => "5%% chance of double item drops",
		2 => "10%% chance of double item drops",
		3 => "15%% chance of double item drops",
		4 => "20%% chance of triple item drops",
		5 => "25%% chance of triple item drops",
	];

	const EXP_DATA_PICKAXE = [
		1 => "x1.5 exp drops from blocks",
		2 => "x2 exp drops from blocks",
		3 => "x2.5 exp drops from blocks",
		4 => "x3 exp drops from blocks",
		5 => "x4 exp drops from blocks",
	];

	const LOOTING_DATA_ROD = [
		1 => "5%% chance of double item drops",
		2 => "10%% chance of double item drops",
		3 => "15%% chance of double item drops",
		4 => "20%% chance of triple item drops",
		5 => "25%% chance of triple item drops",
	];

	const EXP_DATA_ROD = [
		1 => "x1.5 exp drops from fishing",
		2 => "x2 exp drops from fishing",
		3 => "x2.5 exp drops from fishing",
		4 => "x3 exp drops from fishing",
		5 => "x4 exp drops from fishing",
	];

	public function __construct(Player $player, public Pickaxe|Sword $item, string $message = "", bool $error = true){
		$data = new ItemData($item);
		$xp = $data->getXp();
		$level = $data->getLevel();
		$prestige = $data->getPrestige();
		$sp = $data->getSkillPoints();

		$looting = $data->getTreeLevel(ItemData::SKILL_LOOT);
		$xpS = $data->getTreeLevel(ItemData::SKILL_EXP);

		$lootingText = "LOOTING:" . PHP_EOL;
		$tdata = ($item instanceof Pickaxe ? self::LOOTING_DATA_PICKAXE : self::LOOTING_DATA_SWORD);//($item instanceof FishingRod ? self::LOOTING_DATA_ROD : self::LOOTING_DATA_SWORD));
		foreach($tdata as $lvl => $text){
			$lootingText .= TextFormat::GRAY . "- " . ($lvl <= $looting ? TextFormat::GREEN : TextFormat::RED) . $text . PHP_EOL;
		}
		$lootingText .= TextFormat::WHITE . PHP_EOL;

		$xpText = "EXP:" . PHP_EOL;
		$tdata = ($item instanceof Pickaxe ? self::EXP_DATA_PICKAXE : self::EXP_DATA_SWORD);//($item instanceof FishingRod ? self::EXP_DATA_ROD : self::EXP_DATA_SWORD));
		foreach($tdata as $lvl => $text){
			$xpText .= TextFormat::GRAY . "- " . ($lvl <= $xpS ? TextFormat::GREEN : TextFormat::RED) . $text . PHP_EOL;
		}
		$xpText .= TextFormat::WHITE . PHP_EOL;
		parent::__construct(
			"Skill trees",
			($message !== "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			
			"Skill tree data for the item you are holding (" . $item->getVanillaName() . ")" . PHP_EOL . PHP_EOL .
			"Prestige: " . $prestige . PHP_EOL .
			"(Every " . TextFormat::AQUA . "100" . TextFormat::WHITE . " levels, you can prestige your tool for " . TextFormat::RED . "2 divine keys" . TextFormat::WHITE . ", and a chance of leveling up/overclocking one of your enchantments!)" . PHP_EOL . PHP_EOL .
			
			"Level: " . TextFormat::AQUA . $level . TextFormat::WHITE . PHP_EOL .
			"Tool XP: " . TextFormat::YELLOW . number_format($xp) . TextFormat::GRAY . "/" . TextFormat::GREEN . number_format($data->getXpForNextLevel()) . TextFormat::WHITE . PHP_EOL .
			"Available skill points: " . TextFormat::RED . $sp . TextFormat::WHITE . PHP_EOL .
			"(Every " . TextFormat::YELLOW . "10" . TextFormat::WHITE . " levels, your tool will earn " . TextFormat::RED . "1 Skill Point!" . TextFormat::WHITE . ")" . PHP_EOL . PHP_EOL .

			"Trees:" . PHP_EOL .
			$lootingText .
			$xpText .
			"Select an option below!"
		);

		if($level >= 100){
			$this->addButton(new Button("+1 prestige" . PHP_EOL . TextFormat::AQUA . number_format($data->getPrestigeCost()) . " techits"));
		}
		$this->addButton(new Button("[" . $looting . "] LOOTING" . ($looting > 5 && $sp > 0 ? PHP_EOL . TextFormat::GREEN . "Tap to upgrade!" : "")));
		$this->addButton(new Button("[" . $xpS . "] EXP" . ($xpS > 5 && $sp > 0 ? PHP_EOL . TextFormat::GREEN . "Tap to upgrade!" : "")));
		//todo: display skill trees and their progress
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$slot = $player->getInventory()->first($this->item, true);
		if($slot === -1){
			$player->sendMessage(TextFormat::RI . "This item is no longer in your inventory!");
			return;
		}
		$data = new ItemData($item = $player->getInventory()->getItem($slot));
		if($data->getLevel() >= 100){
			if($response == 0){
				if($player->getTechits() < $data->getPrestigeCost()){
					$player->showModal(new SkillTreeUi($player, $item, "You don't have enough techits to prestige this tool!"));
					return;
				}
				$player->showModal(new PrestigeToolUi($player, $item));
				return;
			}
			$response--;
		}

		if($data->getSkillPoints() <= 0){
			$player->showModal(new SkillTreeUi($player, $item, "You don't have any available skill points!"));
			return;
		}
		$looting = $data->getTreeLevel(ItemData::SKILL_LOOT);
		$xpS = $data->getTreeLevel(ItemData::SKILL_EXP);
		if($response == 0){
			if($looting >= 5){
				$player->showModal(new SkillTreeUi($player, $item, "This tree has already reached the max level!"));
				return;
			}
			$data->applySkillPoint(ItemData::SKILL_LOOT);
			$player->getInventory()->setItem($slot, $data->getItem());
			$player->showModal(new SkillTreeUi($player, $data->getItem(), "Looting skill tree has been upgraded!"));
			return;
		}
		if($response == 1){
			if($xpS >= 5){
				$player->showModal(new SkillTreeUi($player, $item, "This tree has already reached the max level!"));
				return;
			}
			$data->applySkillPoint(ItemData::SKILL_EXP);
			$player->getInventory()->setItem($slot, $data->getItem());
			$player->showModal(new SkillTreeUi($player, $data->getItem(), "Exp skill tree has been upgraded!"));
			return;
		}
	}

}