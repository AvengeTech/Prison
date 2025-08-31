<?php namespace prison\quests\uis;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

use prison\Prison;
use prison\PrisonPlayer;
use prison\quests\Quest;

use core\ui\windows\ModalWindow;

class QuestAcceptUi extends ModalWindow{

	public $player;
	public $quest;

	public function __construct(Player $player, ?Quest $quest = null){
		/** @var PrisonPlayer $player */
		$quests = Prison::getInstance()->getQuests();

		if($quest == null){
			$quest = (!isset($quests->random[$player->getName()]) ?
				$quests->getRandomQuest($player->getGameSession()->getRankUp()->getRank()) :
				$quests->random[$player->getName()]
			);
		}

		parent::__construct($quest->getName(), $quest->getMessage("request") . PHP_EOL . PHP_EOL . "Objective: " . $quest->getMessage("requirement") . PHP_EOL . PHP_EOL . "This quest is worth " . $quest->getPoints() . " Quest Points.", "Accept Quest", "Go back");

		$quests->random[$player->getName()] = $quest; //So player can't refresh to keep getting new quests

		$this->player = $player;
		$this->quest = $quest;
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if($response){
			$session = $player->getGameSession()->getQuests();
			$session->setActiveQuest($this->quest);
			$player->sendMessage(TextFormat::GREEN . TextFormat::BOLD . "(!) " . TextFormat::RESET . TextFormat::GRAY . "You have accepted a quest from " . TextFormat::YELLOW . $this->quest->getName() . ": " . $this->quest->getMessage("requirement") . TextFormat::GRAY . " - Please complete to receive " . TextFormat::AQUA . $this->quest->getPoints() . " Quest Points");
			return;
		}
		$player->showModal(new MainQuestUi($player));
	}

}