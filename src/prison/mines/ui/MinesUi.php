<?php namespace prison\mines\ui;

use pocketmine\Server;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\item\Item;

use prison\Prison;
use prison\mines\{
	PrestigeMine,
	PvPMine
};
use prison\PrisonPlayer;

use core\Core;
use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\ItemRegistry;
use core\utils\TextFormat;

class MinesUi extends SimpleForm{

	public $mines = [];

	public function __construct(Player $player, array $mines = [], string $error = ""){
		/** @var PrisonPlayer $player */
		parent::__construct("Mine List", ($error !== "" ? TextFormat::RED . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") . "Select an option below!");

		$mc = Prison::getInstance()->getMines();
		$session = $player->getGameSession()->getMines();
		if(count($mines) === 0){
			$mines = $session->getUnlockedMines();
			$mlist = [
				"normal" => [],
				"prestige" => [],
				"vip" => null,
			];
			foreach($mines as $name){
				$mine = $mc->getMineByName($name);
				if($mine !== null){
					if(strlen($name) == 1){
						$mlist["normal"][] = $mine;
					}elseif($name == "vip"){
						$mlist["vip"] = $mine;
					}elseif($mine instanceof PrestigeMine){
						$mlist["prestige"][] = $mine;
					}
				}
			}
			$this->mines = $mlist;
		}else{
			$this->mines = $mines;
		}

		$this->addButton(new Button("Letter Mines" . PHP_EOL . TextFormat::ITALIC . "Rank up to unlock these!"));
		$this->addButton(new Button("Prestige Mines" . PHP_EOL . TextFormat::ITALIC . "For professional miners only!"));
		$this->addButton(new Button("PvP Mine" . PHP_EOL . TextFormat::ITALIC . "Rare blocks, deadly risk!"));
		$this->addButton(new Button("VIP Mine" . PHP_EOL . TextFormat::ITALIC . "Luxury mine"));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$mc = Prison::getInstance()->getMines();
		$session = ($gs = $player->getGameSession())->getMines();
		$rs = $gs->getRankUp();
		if($gs->getCombat()->isTagged()){
			$player->sendMessage(TextFormat::RI . "You cannot teleport to a plot world while in combat.");
			return;
		}
		if($response == 0){
			$player->showModal(new MineListUi($player, $this->mines["normal"]));
			return;
		}
		if($response == 1){
			if($rs->getPrestige() < 1){
				$player->showModal(new MinesUi($player, $this->mines, "You must prestige at least once to access this menu!"));
				return;
			}
			$player->showModal(new MineListUi($player, $this->mines["prestige"]));
			return;
		}
		if($response == 2){
			$mine = $mc->getMineByName("pvp");
			if($player->getArmorInventory()->getItem(1)->getTypeId() == ItemRegistry::ELYTRA()->getTypeId()){
				$player->showModal(new MinesUi($player, $this->mines, "Please take your Elytra off before entering the PvP mine!"));
				return;
			}
			$player->sendMessage(TextFormat::GI . "Teleported to the " . TextFormat::YELLOW . $mine->getDisplayName());
			Server::getInstance()->dispatchCommand($player, "mine pvp");
			return;
		}
		if($response == 3){
			if($player->getRank() == "default"){
				$player->showModal(new MinesUi($player, $this->mines, "You must have a premium rank to access this mine! Purchase one at " . TextFormat::YELLOW . Core::LINKS["store"]));
				return;
			}
			$mine = $this->mines["vip"];
			if($mine !== null){
				$player->sendMessage(TextFormat::GI . "Teleported to the " . TextFormat::YELLOW . $mine->getDisplayName());
				Server::getInstance()->dispatchCommand($player, "mine " . $mine->getName());
			}
		}
	}

}