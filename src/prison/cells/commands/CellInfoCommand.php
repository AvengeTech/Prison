<?php namespace prison\cells\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\Cell;
use prison\cells\ui\CellInfoUi;

use core\utils\TextFormat;

class CellInfoCommand extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name,$description);
		$this->setPermission("prison.tier3");
		$this->setAliases(["ci"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		/** @var PrisonPlayer $sender */
		if(!$sender->isTier3() || !$sender instanceof Player) return false;

		if(count($args) !== 3){
			$sender->sendMessage(TextFormat::RI . "Usage: /cellinfo <corridor> <row> <cell>");
			return false;
		}

		$corridor = (int) array_shift($args);
		$row = (int) array_shift($args);
		$cell = (int) array_shift($args);

		$cm = Prison::getInstance()->getCells()->getCellManager();
		if($corridor == -1 && $row == -1){
			$cell = $cm->getDisplayCell($cell);
		}else{
			$cell = $cm->getCell($corridor, $row, $cell);
		}

		if(!$cell instanceof Cell){
			$sender->sendMessage(TextFormat::RI . "Invalid cell!");
			return false;
		}

		$sender->showModal(new CellInfoUi($sender, $cell));

		return true;
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}