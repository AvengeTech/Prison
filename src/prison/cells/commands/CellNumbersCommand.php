<?php namespace prison\cells\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use core\utils\TextFormat;

class CellNumbersCommand extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name,$description);
		$this->setPermission("prison.tier3");
		$this->setAliases(["cn"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		/** @var PrisonPlayer $sender */
		if(!$sender->isTier3() || !$sender instanceof Player) return false;

		$cm = Prison::getInstance()->getCells()->getCellManager();

		$cell = $cm->getCellIn($sender);
		if($cell === null){
			$sender->sendMessage(TextFormat::RI . "Please use this subcommand inside a cell");
			return false;
		}

		$sender->sendMessage(TextFormat::GI . "Cell number: " . $cell->getCorridor() . " - " . $cell->getRow() . " - " . $cell->getId());

		$sbutton = $cell->getStoreButton();
		$sender->sendMessage(TextFormat::GI . "Store button location: X:" . $sbutton->getPosition()->getX() . " - Y:" . $sbutton->getPosition()->getY() . " - Z:" . $sbutton->getPosition()->getZ());
		$qbutton = $cell->getQueueButton();
		$sender->sendMessage(TextFormat::GI . "Queue button location: X:" . $qbutton->getPosition()->getX() . " - Y:" . $qbutton->getPosition()->getY() . " - Z:" . $qbutton->getPosition()->getZ());

		return true;
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}