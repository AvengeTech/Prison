<?php namespace prison\cells\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\Cell;

use core\utils\TextFormat;

class CellFloorCommand extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name,$description);
		$this->setPermission("prison.tier3");
		$this->setAliases(["cf"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player && !$sender->isTier3()) return false;
		if(empty($args)){
			$sender->sendMessage(TextFormat::RI . "Usage: /cellfloor <corridor> <row> <cell> <load:save:clear> [name] OR /cellfloor list OR /cellfloor delete <name>");
			return false;
		}

		$lm = Prison::getInstance()->getCells()->getLayoutManager();
		if(count($args) < 4){
			$action = strtolower(array_shift($args));
			switch($action){
				default:
					$sender->sendMessage(TextFormat::RI . "Invalid action!");
					return false;

				case "l":
				case "list":
					$floors = $lm->getFloors();
					if(empty($floors)){
						$sender->sendMessage(TextFormat::RI . "No floors to display.");
						return false;
					}
					$fs = "";
					foreach($floors as $floor){
						$fs .= TextFormat::GRAY . "- " . TextFormat::YELLOW . $floor->getName() . PHP_EOL;
					}
					$sender->sendMessage(TextFormat::GI . "Showing available floors: " . PHP_EOL . $fs);
					return true;

				case "d":
				case "del":
				case "delete":
					if(empty($args)){
						$sender->sendMessage(TextFormat::RI . "Please provide floor name!");
						return false;
					}
					$floor = $lm->getFloor(array_shift($args));
					if($floor == null){
						$sender->sendMessage(TextFormat::RI . "Floor with this name does not exist!");
						return false;
					}
					if($lm->removeFloor($floor->getName())){
						$sender->sendMessage(TextFormat::GI . "Successfully deleted floor " . TextFormat::YELLOW . $floor->getName());
						return true;
					}
					$sender->sendMessage(TextFormat::RI . "Couldn't remove this floor, please try again later!");
					return false;
			}
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


		switch(strtolower(array_shift($args))){
			default:
				$sender->sendMessage(TextFormat::RI . "Invalid action provided!");
				return false;

			case "clear":
			case "cl":
			case "c":
				$cell->clearFloor();
				$sender->sendMessage(TextFormat::GI . "Successfully cleared floor from " . TextFormat::YELLOW . "Cell " . $cell->getName());
				return true;

			case "save":
			case "s":
			case "new":
			case "n":
				if(empty($args)){
					$sender->sendMessage(TextFormat::RI . "No name provided!");
					return false;
				}
				$name = strtolower(array_shift($args));
				$floor = $lm->getFloor($name);
				if($floor != null){
					$sender->sendMessage(TextFormat::RI . "Floor with this name already exists!");
					return false;
				}

				$lm->createFloorFrom($cell, $name);
				$sender->sendMessage(TextFormat::GI . "Saved new floor titled " . TextFormat::YELLOW . $name);
				return true;

			case "load":
			case "l":
			case "paste":
			case "p":
				if(empty($args)){
					$sender->sendMessage(TextFormat::RI . "No name provided!");
					return false;
				}
				$name = strtolower(array_shift($args));
				$floor = $lm->getFloor($name);
				if($floor == null){
					$sender->sendMessage(TextFormat::RI . "Invalid floor selected!");
					return false;
				}

				$floor->apply($cell);
				$sender->sendMessage(TextFormat::GI . "Applied cell floor to " . TextFormat::YELLOW . "Cell " . $cell->getName() . TextFormat::GRAY . " titled " . TextFormat::AQUA . $name);
				return true;
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}