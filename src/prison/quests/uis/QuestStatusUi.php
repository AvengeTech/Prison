<?php namespace prison\quests\uis;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

use prison\PrisonPlayer;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

class QuestStatusUi extends SimpleForm{

	public $player;

	public $turnin = false;

	public function __construct(Player $player){
		/** @var PrisonPlayer $player */
		$this->player = $player;

		$session = $player->getGameSession()->getQuests();
		if(!$session->hasActiveQuest()){
			parent::__construct("Quest Status", "You do not have a quest active right now!");
		}else{
			$quest = $session->getCurrentQuest();
			if($quest->isComplete()){
				$message = $quest->getMessage("complete");
				$this->addButton(new Button("Turn in"));
				$this->turnin = true;
			}else{
				$message = $quest->getMessage("incomplete");
			}
			parent::__construct("Quest Status", $quest->getMessage("requirement") . PHP_EOL . PHP_EOL . $message . PHP_EOL . PHP_EOL . $quest->getProgressString());
		}
		$this->addButton(new Button("Back"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$session = $player->getGameSession()->getQuests();
		if($this->turnin){
			if($response == 0){
				if(!$session->hasActiveQuest()){
					$player->sendMessage(TextFormat::RED . TextFormat::BOLD . "(!) " . TextFormat::RESET . TextFormat::GRAY . "You have no active quest!");
					return;
				}
				$quest = $session->getCurrentQuest();
				$quest->turnin($session);

				$player->sendMessage(TextFormat::DARK_PURPLE . $quest->getName() . ": " . TextFormat::YELLOW . $quest->getMessage("complete") . PHP_EOL . TextFormat::DARK_PURPLE . TextFormat::BOLD . "(!) " . TextFormat::RESET . TextFormat::GRAY . "Earned " . TextFormat::AQUA . "+" . $quest->getPoints() . " quest points " . TextFormat::GRAY . "for completing this task!");
				return;
			}
			$player->showModal(new MainQuestUi($player));
			return;
		}
		$player->showModal(new MainQuestUi($player));
	}

}