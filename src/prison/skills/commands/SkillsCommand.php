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
use prison\skills\uis\SkillUi;

class SkillsCommand extends Command{

	public function __construct(
		private Prison $plugin,
		string $name,
		string $description
	){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$sender instanceof Player){
			$sender->sendMessage(TextFormat::RI . "You cannot use this command.");
			return false;
		}
		
		/** @var ?Player $player */
		$player = (isset($args[0]) && $sender->isTier3() ? Server::getInstance()->getPlayerByPrefix($args[0]) : $sender);

		if(is_null($player)){
			$sender->sendMessage(TextFormat::RI . 'Player is not online');
			return false;
		}

		$targetIsSender = $sender === $player;

		$sender->showModal(new SkillUi($sender, $player->getGameSession()->getSkills(), "", true, $targetIsSender));
		return true;
	}
}