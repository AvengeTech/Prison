<?php namespace prison\cells\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\Cell;

use core\utils\TextFormat;

class CellLayoutCommand extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name,$description);
		$this->setPermission("prison.tier3");
		$this->setAliases(["cl"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player && !$sender->isTier3()) return false;

		if(empty($args)){
			$sender->sendMessage(TextFormat::RI . "Usage: /celllayout <corridor> <row> <cell> <load:save:delete> [name] [floor name=default] [new floor=false]");
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
					$layouts = $lm->getLayouts();
					if(empty($layouts)){
						$sender->sendMessage(TextFormat::RI . "No layouts to display.");
						return false;
					}
					$ls = "";
					foreach($layouts as $layout){
						$ls .= TextFormat::GRAY . "- " . TextFormat::AQUA . $layout->getName() . TextFormat::GRAY . " -|- " . TextFormat::ITALIC . $layout->getDescription() . TextFormat::RESET . PHP_EOL;
					}
					$sender->sendMessage(TextFormat::GI . "Showing available layouts: " . PHP_EOL . $ls);
					return true;

				case "d":
				case "del":
				case "delete":
					if(empty($args)){
						$sender->sendMessage(TextFormat::RI . "Please provide layout name!");
						return false;
					}
					$layout = $lm->getLayout(array_shift($args));
					if($layout == null){
						$sender->sendMessage(TextFormat::RI . "Layout with this name does not exist!");
						return false;
					}
					if($lm->removeLayout($layout->getName())){
						$sender->sendMessage(TextFormat::GI . "Successfully deleted layout " . TextFormat::YELLOW . $layout->getName());
						return true;
					}
					$sender->sendMessage(TextFormat::RI . "Couldn't remove this layout, please try again later!");
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

			case "l":
			case "list":
				$layouts = $lm->getLayouts();
				if(empty($layouts)){
					$sender->sendMessage(TextFormat::RI . "No layouts to display.");
					return false;
				}
				$ls = "";
				foreach($layouts as $layout){
					$ls .= TextFormat::GRAY . "- " . TextFormat::AQUA . $layout->getName() . PHP_EOL;
				}
				$sender->sendMessage(TextFormat::GI . "Showing available layouts: " . PHP_EOL . $ls);
				return true;

			case "delete":
			case "del":
			case "d":
				$cell->clear();
				$sender->sendMessage(TextFormat::GI . "Successfully cleared blocks from " . TextFormat::YELLOW . "Cell " . $cell->getName());
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
				$layout = $lm->getLayout($name);
				if($layout != null){
					$sender->sendMessage(TextFormat::RI . "Layout with this name already exists!");
					return false;
				}

				$floorName = "";
				$saveFloor = false;
				if(!empty($args)){
					$floorName = strtolower(array_shift($args) ?? $name);
					if(strlen($floorName) <= 3){
						$sender->sendMessage(TextFormat::RI . "Must enter longer floor name Leave blank for default!");
						return false;
					}

					$saveFloor = (bool) array_shift($args) ?? false;
					if($saveFloor){
						$floor = $lm->getFloor($floorName);
						if($floor != null){
							$sender->sendMessage(TextFormat::RI . "Floor by this name already exists!");
							return false;
						}
						$lm->createFloorFrom($cell, $floorName);
						$sender->sendMessage(TextFormat::GI . "Saved new floor titled " . TextFormat::YELLOW . $floorName);
					}
				}

				$lm->createLayoutFrom($cell, $name, "yooo created with layout manager", 1, $floorName);
				$sender->sendMessage(TextFormat::GI . "Created cell layout from " . TextFormat::YELLOW . "Cell " . $cell->getName() . TextFormat::GRAY . " titled " . TextFormat::AQUA . $name);
				return true;

			case "load":
			case "paste":
			case "p":
				if(empty($args)){
					$sender->sendMessage(TextFormat::RI . "No name provided!");
					return false;
				}
				$name = strtolower(array_shift($args));
				$layout = $lm->getLayout($name);
				if($layout == null){
					$sender->sendMessage(TextFormat::RI . "Invalid layout selected!");
					return false;
				}

				$floorName = strtolower(array_shift($args) ?? $name);
				$floor = $lm->getFloor($floorName);
				if($floor == null){
					$sender->sendMessage(TextFormat::RI . "Floor by this name doesn't exists!");
					return false;
				}
				$layout->apply($cell, true, $floor);
				$sender->sendMessage(TextFormat::GI . "Applied cell layout to " . TextFormat::YELLOW . "Cell " . $cell->getName() . TextFormat::GRAY . " titled " . TextFormat::AQUA . $name . TextFormat::GRAY . "(Custom floor: " . TextFormat::YELLOW . $floor->getName() . TextFormat::GRAY . ")");
				return true;

				//$layout->apply($cell);
				//$sender->sendMessage(TextFormat::GI . "Applied cell layout to " . TextFormat::YELLOW . "Cell " . $cell->getName() . TextFormat::GRAY . " titled " . TextFormat::AQUA . $name);
				//return true;
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}