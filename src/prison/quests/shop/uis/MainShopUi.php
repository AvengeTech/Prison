<?php namespace prison\quests\shop\uis;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

use prison\Prison;
use prison\PrisonPlayer;
use prison\quests\uis\MainQuestUi;

use core\ui\elements\simpleForm\Button;
use core\ui\windows\SimpleForm;

class MainShopUi extends SimpleForm{

	public array $categories = [];

	public function __construct(Player $player, ?string $message = null){
		/** @var PrisonPlayer $player */
		$categories = Prison::getInstance()->getQuests()->getShop()->getCategories();
		foreach($categories as $category){
			$this->categories[] = $category;
			$this->addButton($category->getButton());
		}
		$this->addButton(new Button("Go back"));

		$session = $player->getGameSession()->getQuests();
		parent::__construct("Point Shop", ($message == null ? "" : $message . TextFormat::RESET . PHP_EOL . PHP_EOL) . "You have " . $session->getPoints() . " quest points to spend!" . PHP_EOL . PHP_EOL . "What would you like to do?");
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		foreach($this->categories as $key => $category){
			if($key == $response){
				$player->showModal(new CategoryUi($category->getId(), $player));
				return;
			}
		}
		$player->showModal(new MainQuestUi($player));
	}

}