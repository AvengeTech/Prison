<?php namespace prison\quests\uis;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

use prison\PrisonPlayer;
use prison\quests\shop\uis\MainShopUi;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use core\network\Links;

class MainQuestUi extends SimpleForm{

	public bool $active = false;

	public function __construct(public Player $player) {
		/** @var PrisonPlayer $player */
		parent::__construct("Quest Manager", "What would you like to do?");

		$session = $player->getGameSession()->getQuests();

		if($session->hasActiveQuest()){
			$this->addButton(new Button("View Quest Status"));
			$this->active = true;
		}else{
			$this->addButton(new Button("Accept Random Quest"));
			$this->addButton(new Button("Select your quest!" . PHP_EOL . count($session->getModules()) . " available!"));
		}
		$this->addButton(new Button("Spend Quest Points" . PHP_EOL . $session->getPoints() . " available"));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if($this->active){
			if($response == 0){
				$player->showModal(new QuestStatusUi($player));
				return;
			}
			if($response == 1){
				$player->showModal(new MainShopUi($player));
				return;
			}
			return;
		}
		$session = $player->getGameSession()->getQuests();
		if($response == 0){
			if($session->hasCooldown()){
				$player->sendMessage(TextFormat::RED . TextFormat::BOLD . "(!) " . TextFormat::RESET . TextFormat::GRAY . "You may complete another quest in " . TextFormat::WHITE . $session->getFormattedCooldown());
				return;
			}
			$player->showModal(new QuestAcceptUi($player));
			return;
		}
		if($response == 1){
			if($player->getRank() == "default"){
				$player->sendMessage(TextFormat::RED . TextFormat::BOLD . "(!) " . TextFormat::RESET . TextFormat::GRAY . "You must have a premium rank to choose your questmaster! Purchase one at " . TextFormat::YELLOW . Links::SHOP);
				return;
			}
			$player->showModal(new ModuleUi($player));
			return;
		}
		if($response == 2){
			$player->showModal(new MainShopUi($player));
		}
	}

}