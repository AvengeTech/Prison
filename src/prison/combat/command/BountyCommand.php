<?php namespace prison\combat\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\combat\ui\bounty\BountyUi;

class BountyCommand extends Command{
	
	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["bounties", "target"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player) $sender->showModal(new BountyUi($sender));
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}