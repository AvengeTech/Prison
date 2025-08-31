<?php namespace prison\guards\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;
use prison\guards\ui\ShowBinsUi;

use core\utils\TextFormat;

class BinCommand extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["b"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player){
			$session = $sender->getGameSession()->getGuards();
			if(empty($session->getBins())){
				$sender->sendMessage(TextFormat::RI . "You have no items in the Lost and Found bin!");
				return;
			}
			$sender->showModal(new ShowBinsUi($sender));
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}