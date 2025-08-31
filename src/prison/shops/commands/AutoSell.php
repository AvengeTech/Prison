<?php namespace prison\shops\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;
use prison\settings\PrisonSettings;

use core\network\Links;
use core\rank\Structure;
use core\utils\TextFormat;

class AutoSell extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["as"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		/** @var PrisonPlayer $sender */
		$rh = Structure::RANK_HIERARCHY[$sender->getRank()];
		if($rh < 5){
			$sender->sendMessage(TextFormat::RI . "Autosell requires at least WITHER rank to toggle! Visit " . TextFormat::YELLOW . Links::SHOP . TextFormat::GRAY . " to buy it!");
			return;
		}
		$session = $sender->getGameSession()->getSettings();
		$can = $session->getSetting(PrisonSettings::AUTOSELL);
		if($can){
			$sender->sendMessage(TextFormat::RI . "AutoSell is now off!");
		}else{
			$sender->sendMessage(TextFormat::GI . "AutoSell is now on! All mined blocks will be automatically sold!");
		}
		$session->setSetting(PrisonSettings::AUTOSELL, !$can);
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}