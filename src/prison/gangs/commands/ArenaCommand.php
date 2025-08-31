<?php namespace prison\gangs\commands;

use core\utils\conversion\LegacyBlockIds;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\world\Position;
use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;

use core\utils\TextFormat;

class ArenaCommand extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name,$description);
		$this->setPermission("prison.tier3");
		$this->setAliases(["al"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		/** @var PrisonPlayer $sender */
		if(!$sender->isTier3()) return false;

		$gangs = $this->plugin->getGangs();
		$bm = $gangs->getGangManager()->getBattleManager();

		if($commandLabel == "al"){
			$astr = "";
			foreach($bm->getArenas() as $arena){
				$astr .= ($bm->isArenaOccupied($arena->getId()) ? TextFormat::RED : TextFormat::GREEN) . $arena->getId() . TextFormat::GRAY . ", ";
			}
			$sender->sendMessage(TextFormat::GI . "Showing available arena IDs: " . $astr);
			return true;
		}

		if(count($args) < 2){
			$sender->sendMessage(TextFormat::RI . "Usage: /arena <id> <main:center> <corner> OR /arena <id> half <1 or 2> <corner>");
			return false;
		}
		$arena = $bm->getArena((int) array_shift($args));
		if($arena == null){
			$sender->sendMessage(TextFormat::RI . "Invalid arena!");
			return false;
		}
		$level = $arena->getLevel();
		switch(strtolower(array_shift($args))){
			default:
				$sender->sendMessage(TextFormat::RI . "Invalid option! Usage: /arena <main:center> <corner> OR /arena half <1 or 2> <corner>");
				return false;

			case "main":
				if(empty($args)){
					$sender->sendMessage(TextFormat::RI . "Corner not specified! <1 or 2>");
					return false;
				}
				switch((int) array_shift($args)){
					default:
						$sender->sendMessage(TextFormat::RI . "Invalid arena corner specified! <1 or 2>");
						return false;
					case 1:
						$corner = $arena->getCorner1();
						while($level->getBlock($corner)->getTypeId() !== LegacyBlockIds::legacyIdToTypeId(0)){
							$corner = $corner->add(0, 1, 0);
						}
						$sender->teleport(Position::fromObject($corner, $level));
						$sender->sendMessage(TextFormat::GI . "Teleported to Arena " . $arena->getId() . " - Corner 1!");
						return true;
					case 2:
						$corner = $arena->getCorner2();
						while($level->getBlock($corner)->getTypeId() !== LegacyBlockIds::legacyIdToTypeId(0)){
							$corner = $corner->add(0, 1, 0);
						}
						$sender->teleport(Position::fromObject($corner, $level));
						$sender->sendMessage(TextFormat::GI . "Teleported to Arena " . $arena->getId() . " - Corner 2!");
						return true;
				}
			case "center":
				$center = $arena->getCenter();
				if(empty($args)){
					$sender->sendMessage(TextFormat::RI . "Center corner not specified! <1 or 2>");
					return false;
				}
				switch((int) array_shift($args)){
					default:
						$sender->sendMessage(TextFormat::RI . "Invalid center corner specified! <1 or 2>");
						return false;
					case 1:
						$corner = $center->getCorner1();
						while($level->getBlock($corner)->getTypeId() !== LegacyBlockIds::legacyIdToTypeId(0)){
							$corner = $corner->add(0, 1, 0);
						}
						$sender->teleport(Position::fromObject($corner, $level));
						$sender->sendMessage(TextFormat::GI . "Teleported to Arena " . $arena->getId() . " - Center - Corner 1!");
						return true;
					case 2:
						$corner = $center->getCorner2();
						while($level->getBlock($corner)->getTypeId() !== LegacyBlockIds::legacyIdToTypeId(0)){
							$corner = $corner->add(0, 1, 0);
						}
						$sender->teleport(Position::fromObject($corner, $level));
						$sender->sendMessage(TextFormat::GI . "Teleported to Arena " . $arena->getId() . " - Center - Corner 2!");
						return true;

					case "dot":
						$dot = $center->getDot();
						while($level->getBlock($dot)->getTypeId() !== LegacyBlockIds::legacyIdToTypeId(0)){
							$dot = $dot->add(0, 1, 0);
						}
						$sender->teleport(Position::fromObject($dot, $level));
						$sender->sendMessage(TextFormat::GI . "Teleported to Arena " . $arena->getId() . " - Direct Center!");
						return true;
				}
			case "half":
				if(empty($args)){
					$sender->sendMessage(TextFormat::RI . "Half not specified! <1 or 2>");
					return false;
				}
				$half = $arena->getHalf(($hid = (int) array_shift($args)));
				if($half == null){
					$sender->sendMessage(TextFormat::RI . "Invalid half specified! <1 or 2>");
					return false;
				}

				if(empty($args)){
					$sender->sendMessage(TextFormat::RI . "Corner not specified! <1 or 2>");
					return false;
				}
				switch((int) array_shift($args)){
					default:
						$sender->sendMessage(TextFormat::RI . "Invalid half corner specified! <1 or 2>");
						return false;
					case 1:
						$corner = $half->getCorner1();
						while($level->getBlock($corner)->getTypeId() !== LegacyBlockIds::legacyIdToTypeId(0)){
							$corner = $corner->add(0, 1, 0);
						}
						$sender->teleport(Position::fromObject($corner, $level));
						$sender->sendMessage(TextFormat::GI . "Teleported to Arena " . $arena->getId() . " - Half " . $hid . " - Corner 1!");
						return true;
					case 2:
						$corner = $half->getCorner2();
						while($level->getBlock($corner)->getTypeId() !== LegacyBlockIds::legacyIdToTypeId(0)){
							$corner = $corner->add(0, 1, 0);
						}
						$sender->teleport(Position::fromObject($corner, $level));
						$sender->sendMessage(TextFormat::GI . "Teleported to Arena " . $arena->getId() . " - Half " . $hid . " - Corner 2!");
						return true;
				}
			case "halfsp":
				if(empty($args)){
					$sender->sendMessage(TextFormat::RI . "Half not specified! <1 or 2>");
					return false;
				}
				$half = $arena->getHalf(($hid = (int) array_shift($args)));
				if($half == null){
					$sender->sendMessage(TextFormat::RI . "Invalid half specified! <1 or 2>");
					return false;
				}

				if(empty($args)){
					$sender->sendMessage(TextFormat::RI . "Total points not specified! <up to 7>");
					return false;
				}
				$points = (int) array_shift($args);
				if($points < 1 || $points > 7){
					$sender->sendMessage(TextFormat::RI . "Total points must be between 1-7!");
					return false;
				}

				if(empty($args)){
					$sender->sendMessage(TextFormat::RI . "Points not specified! <1 to however many total>");
					return false;
				}
				$point = (int) array_shift($args);
				if($point < 1 || $point > $points){
					$sender->sendMessage(TextFormat::RI . "Point value must be between 1 and total points! (" . $points . ")");
					return false;
				}

				$sender->teleport($half->getSpawnpoint($points, $point));
				$sender->sendMessage(TextFormat::GI . "Teleported to Arena " . $arena->getId() . " - Half " . $hid . " - Point " . $point . " (out of " . $points . ")");
				break;
		}
		return true;
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}