<?php namespace prison\cells\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\cells\{
	Cell,
	CellHolder
};
use prison\cells\stores\ui\{
	manage\ManageStoresUi,
	view\ViewStoresUi
};
use prison\cells\ui\{
	CellInfoUi,
	ConfirmCellClearUi,
	CommandHelpUi,
	ManageCellUi,
	ManageStyleUi,
};

use core\Core;
use core\user\User;
use core\utils\TextFormat;

class CellsCommand extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["c", "cell", "mc", "mycell"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player){
			$cm = ($cc = Prison::getInstance()->getCells())->getCellManager();

			$subs = [
				"about", "info", "i", "details",
				"manage", "m",
				"layout", "style",
				"clearstyle", "clear", "cs",
				"store", "stores",
				"goto", "tp",
				"managestore", "managestores", "ms",
			];

			if(count($args) == 0 || !in_array(($sub = strtolower(array_shift($args))), $subs)){
				$sender->showModal(new CommandHelpUi($sender));
				return;
			}

			$cellAction = function(Cell $cell, ?CellHolder $holder, string $sub) use($sender, $cc) : void{
				if(!$sender->isConnected()) return;
				if($holder === null){
					$sender->sendMessage(TextFormat::RI . "This cell has not been purchased!");
					return;
				}

				switch($sub){
					default:
						$sender->showModal(new CommandHelpUi($sender));
						break;
					case "about":
					case "details":
					case "info":
					case "i":
						$sender->showModal(new CellInfoUi($sender, $cell));
						break;
					case "manage":
					case "m":
						if(!$cell->getHolderManager()->isOwner($sender)){
							$sender->sendMessage(TextFormat::RI . "You must own this cell to manage it!");
							return;
						}
						$sender->showModal(new ManageCellUi($sender, $cell));
						break;
					case "style":
					case "layout":
						if(!$cell->getHolderManager()->isOwner($sender) && !$sender->isTier3()){
							$sender->sendMessage(TextFormat::RI . "You must own this cell to edit the layout!");
							return;
						}
						$lm = $cc->getLayoutManager();
						if($lm->hasCooldown($sender)){
							$sender->sendMessage(TextFormat::RI . "You must wait another " . TextFormat::YELLOW . $lm->getCooldown($sender) . " seconds" . TextFormat::GRAY . " before editing your cell style again!");
							return;
						}
						$sender->showModal(new ManageStyleUi($sender, $cell));
						break;
					case "clearstyle":
					case "clear":
					case "cs":
						if(!$cell->getHolderManager()->isOwner($sender) && !$sender->isTier3()){
							$sender->sendMessage(TextFormat::RI . "You must own this cell to edit the layout!");
							return;
						}
						$sender->showModal(new ConfirmCellClearUi($sender, $cell));
						break;
					case "stores":
					case "store":
						$sm = $holder->getStoreManager();
						if(empty($sm->getStores(true))){
							$sender->sendMessage(TextFormat::RI . "This cell holder has no cell stores open!");
							return;
						}
						if($holder->getXuid() == $sender->getXuid()){
							$sender->sendMessage(TextFormat::RI . "You cannot buy from your own stores! To manage them, type " . TextFormat::YELLOW . "/mycell managestores");
							return;
						}
						$sender->showModal(new ViewStoresUi($sender, $cell, $holder));
						break;
					case "goto":
					case "tp":
						$cell->gotoFront($sender);
						$sender->sendMessage(TextFormat::RI . "Teleported to the front of this cell!");
						break;
					case "managestores":
					case "managestore":
					case "ms":
						if(!$cell->getHolderManager()->isHolder($sender)){
							$sender->sendMessage(TextFormat::RI . "You must at least be in this cell's queue to manage the store!");
							return;
						}
						$sender->showModal(new ManageStoresUi($sender, $cell));
						break;
				}
			};

			if(!in_array(strtolower($commandLabel), ["mc", "mycell"])){
				if(count($args) == 0){
					$cell = $cm->getCellIn($sender);
					if($cell === null){
						$sender->sendMessage(TextFormat::RI . "Please use this subcommand inside a cell, or type /cells info <player>");
						return;
					}
					$holder = $cell->getHolderManager()->getOwner();
				}else{
					$name = array_shift($args);
					Core::getInstance()->getUserPool()->useUser($name, function(User $user) use($sender, $cm, $cellAction, $sub) : void{
						if(!$sender->isConnected()) return;
						if(!$user->valid()){
							$sender->sendMessage(TextFormat::RI . "Player never seen!");
							return;
						}
						$cells = $cm->getPlayerCells($user, !in_array($sub, ["stores", "store", "managestore", "managestores", "ms", "info", "i", "about", "details", "goto", "tp"]));
						if(count($cells) == 0){
							$sender->sendMessage(TextFormat::RI . "This player does not own a cell!");
							return;
						}
						$cell = array_shift($cells);
						$holder = $cell->getHolderBy($user);
						$cellAction($cell, $holder, $sub);
					});
					return;
				}
			}else{
				$cells = $cm->getPlayerCells($sender, ($sc = !in_array($sub, ["stores", "store", "managestore", "managestores", "ms", "info", "i", "about", "details", "goto", "tp"])));
				if(count($cells) == 0){
					$sender->sendMessage(TextFormat::RI . (!$sc ? "You must at least be in a cell queue to manage your stores!" : "You do not own a cell!"));
					return;
				}
				$cell = array_shift($cells);
				$holder = $cell->getHolderBy($sender);
			}

			$cellAction($cell, $holder, $sub);
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}