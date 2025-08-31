<?php namespace prison\leaderboards\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\leaderboards\ui\LeaderboardPrizesUi;

class Prizes extends Command{

	public function __construct(public Prison $plugin, $name, $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		/** @var PrisonPlayer $sender */
		if(!$sender instanceof Player) return;

		$sender->showModal(new LeaderboardPrizesUi());
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}