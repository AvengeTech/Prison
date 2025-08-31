<?php namespace prison\combat\ui\bounty;

use pocketmine\{
	player\Player,
	Server
};

use prison\Prison;
use prison\PrisonPlayer;

use core\ui\elements\simpleForm\Button;
use core\ui\windows\SimpleForm;

use core\utils\TextFormat;

class ActiveBountiesUi extends SimpleForm{

	public $bounties = [];

	public function __construct(string $error = ""){
		parent::__construct("Active Bounties", ($error !== "" ? TextFormat::BOLD . TextFormat::RED . "Error: " . TextFormat::RESET . TextFormat::RED . $error . TextFormat::WHITE . "\n" : "") . "Tap on an active bounty to view it's details.");

		$this->addButton(new Button("Refresh"));

		$bl = Prison::getInstance()->getCombat()->getBounties();
		$key = 1;
		foreach($bl as $name => $value){
			$this->bounties[$key] = $name;
			$this->addButton(new Button(TextFormat::YELLOW . $name . TextFormat::DARK_GRAY . " - " . TextFormat::AQUA . number_format($value) . " Techits" . TextFormat::DARK_GRAY . "\n" . TextFormat::RED . "Tap to view"));
		}
		$this->addButton(new Button("Go back"));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if($response == 0){
			$player->showModal(new ActiveBountiesUi());
			return;
		}
		$bounties = Prison::getInstance()->getCombat()->getBounties();

		foreach($this->bounties as $key => $name){
			if($response + 1 == $key){
				$p = Server::getInstance()->getPlayerExact($name);
				if(!$p instanceof Player){
					$player->showModal(new ActiveBountiesUi("Player is no longer online!"));
					return;
				}
				if($p === $player){
					$player->showModal(new ActiveBountiesUi("You cannot edit your own bounty!"));
					return;
				}
				/** @var PrisonPlayer $p */
				$ses = $p->getGameSession()->getCombat();
				if(!$ses->hasBounty()){
					$player->showModal(new ActiveBountiesUi("Player no longer has a bounty!"));
					return;
				}
				$player->showModal(new ViewBountyUi($player, $name));
				return;
			}
		}
		$player->showModal(new BountyUi($player));
	}

}
