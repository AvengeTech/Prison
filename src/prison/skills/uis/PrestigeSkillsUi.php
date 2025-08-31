<?php namespace prison\skills\uis;

use pocketmine\player\Player;
use core\utils\TextFormat;

use prison\PrisonPlayer;

use core\ui\windows\ModalWindow;
use prison\skills\events\PrestigeSkillEvent;
use prison\skills\SkillsComponent;

class PrestigeSkillsUi extends ModalWindow{

	public function __construct(Player $player, private SkillsComponent $component){
		parent::__construct(
			"Prestige tool?",
			"Are you sure you would like to prestige your skills for " . TextFormat::AQUA . number_format($component->getPrestigeCost()) . TextFormat::WHITE . " techits?" . "\n" . "\n" .
			"When you prestige your skills you earn " . TextFormat::RED . "2 divine keys" . TextFormat::WHITE . ", and add " . TextFormat::AQUA . 10 . TextFormat::WHITE . " levels to all skills per prestige!)" . "\n" . "\n",
			"Prestige Skills",
			"Go back"
		);
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		if($response){
			if($player->getTechits() < $this->component->getPrestigeCost()){
				$player->showModal(new SkillUi($player, $this->component, "You don't have enough techits to prestige your skills!"));
				return;
			}

			$event = new PrestigeSkillEvent($player, $this->component);
			$event->call();

			if($event->isCancelled()) return;
			
			$this->component->prestige();

			$player->sendMessage(TextFormat::GI . "Your skills has been prestiged! You earned " . TextFormat::RED . "2 divine keys");
		}else{
			$player->showModal(new SkillUi($player, $this->component));
		}
	}
}