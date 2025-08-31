<?php namespace prison\mines\ui;

use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\item\Item;

use prison\PrisonPlayer;

use core\Core;
use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class MineListUi extends SimpleForm{

	public $mines = [];
	public $before;

	public function __construct(Player $player, array $mines = [], ?SimpleForm $before = null, string $error = "") {
		/** @var PrisonPlayer $player */
		parent::__construct("Mine Select", ($error !== "" ? TextFormat::RED . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") . "Select a mine below to warp to it!");
		$this->before = $before;
		foreach($mines as $mine){
			$this->mines[] = $mine;
			$this->addButton(new Button($mine->getDisplayName()));
		}
		$this->addButton(new Button("Go back"));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$session = ($gs = $player->getGameSession())->getMines();
		if($gs->getCombat()->isTagged()){
			$player->sendMessage(TextFormat::RI . "You cannot teleport to a plot world while in combat.");
			return;
		}
		$mine = $this->mines[$response] ?? null;
		if($mine !== null){
			$player->sendMessage(TextFormat::GI . "Teleported to " . TextFormat::YELLOW . $mine->getDisplayName());
			Server::getInstance()->dispatchCommand($player, "mine " . $mine->getName());
			return;
		}
		$player->showModal($this->before === null ? new MinesUi($player) : $this->before);
	}

}