<?php namespace prison\quests\uis;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

use prison\Prison;
use prison\PrisonPlayer;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

class ModuleUi extends SimpleForm{

	public $player;

	public $modules = [];

	public function __construct(Player $player) {
		/** @var PrisonPlayer $player */
		$this->player = $player;
		$quests = Prison::getInstance()->getQuests();
		$session = $player->getGameSession()->getQuests();

		$modules = $session->getModules();

		$key = 0;
		foreach($modules as $module){
			$this->modules[] = $module;
			$key++;
			$quest = $quests->getClonedQuest($module);
			$this->addButton(new Button($quest->getName() . PHP_EOL . "Tap to summon!"));
		}
		$this->addButton(new Button("Go back"));

		parent::__construct("Quest Modules", "Use these to summon Questmasters! You have " . count($modules) . "/" . count($quests->getQuests()) . " modules unlocked. You can find more Quest Modules in Mystery Boxes!");
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$quests = Prison::getInstance()->getQuests();
		$session = $player->getGameSession()->getQuests();

		foreach($this->modules as $key => $module){
			if($response == $key){
				if($session->hasCooldown()){
					$player->sendMessage(TextFormat::RED . TextFormat::BOLD . "(!) " . TextFormat::RESET . TextFormat::GRAY . "You may complete another quest in " . TextFormat::WHITE . $session->getFormattedCooldown());
					return;
				}
				$quest = $quests->getClonedQuest($module);
				$quest->selected = true;
				$player->showModal(new QuestAcceptUi($player, $quest));
				return;
			}
		}
		$player->showModal(new MainQuestUi($player));
	}

}