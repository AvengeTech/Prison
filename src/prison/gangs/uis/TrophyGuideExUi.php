<?php namespace prison\gangs\uis;

use pocketmine\player\Player;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use prison\PrisonPlayer;

class TrophyGuideExUi extends SimpleForm{

	public function __construct(string $name, string $description){
		parent::__construct("Trophy Guide (continued)",
			$name . PHP_EOL . PHP_EOL . $description
		);
		$this->addButton(new Button("Go back"));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$player->showModal(new TrophyGuideUi($player));
	}

}