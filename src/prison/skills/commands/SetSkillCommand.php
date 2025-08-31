<?php namespace prison\skills\commands;

use core\utils\TextFormat;
use pocketmine\command\{
	Command,
	CommandSender
};
use pocketmine\Server;
use prison\{
	Prison,
	PrisonPlayer as Player,
	skills\Skill
};

class SetSkillCommand extends Command{

	public function __construct(
		private Prison $plugin,
		string $name,
		string $description
	){
		parent::__construct($name, $description);
		$this->setPermission("prison.tier3");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if($sender instanceof Player && !($sender->isTier3())){
			$sender->sendMessage(TextFormat::RI . "You cannot use this command.");
			return false;
		}

		if(count($args) < 1){
			$sender->sendMessage(TextFormat::RN . "Usage: /setskill <string: skill> [int: level] [string: player]");
			return false;
		}

		if(!in_array(($skill = $args[0]), [Skill::SKILL_AXE_COMBAT, Skill::SKILL_COMBAT, Skill::SKILL_FARMING, Skill::SKILL_FISHING, Skill::SKILL_MINING])){
			$sender->sendMessage(TextFormat::RI . "Skill does not exist!");
			return false;
		}

		$level = (isset($args[1]) ? intval($args[1]) : 1);
		/** @var Player $player */
		$player = (isset($args[2]) ? Server::getInstance()->getPlayerByPrefix(($target = $args[2])) : $sender);
		$targetIsSender = $sender === $player;

		if(isset($args[2]) && is_null($player)){
			$sender->sendMessage(TextFormat::RI . "Player is not online!");
			return false;
		}

		$player->getSkill($skill)->setLevel($level);
		$sender->sendMessage(TextFormat::GI . "You set " . ($targetIsSender ? "your" : $target . "'s") . ' ' . str_replace('_', ' ', $skill) . " skill level to " . TextFormat::LIGHT_PURPLE . $level . TextFormat::GRAY . "!");
		return true;
	}
}