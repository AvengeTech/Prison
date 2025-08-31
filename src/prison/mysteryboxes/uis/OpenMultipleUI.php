<?php namespace prison\mysteryboxes\uis;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

use prison\mysteryboxes\pieces\MysteryBox;
use prison\PrisonPlayer;

use core\ui\elements\customForm\{
	Label,
	Slider
};
use core\ui\windows\CustomForm;
use core\utils\conversion\LegacyItemIds;

class OpenMultipleUI extends CustomForm{

	public function __construct(Player $player, public MysteryBox $box){
		/** @var PrisonPlayer $player */
		$session = $player->getGameSession()->getMysteryBoxes();
		$keys = $session->getKeys($box->getTier());

		parent::__construct("Use multiple keys");

		$empty = 0;
		for($i = 0; $i <= 35; $i++){
			if($player->getInventory()->getItem($i)->isNull()) $empty++;
		}

		$this->addElement(new Label("Use this menu to open multiple Mystery Boxes at once!" . PHP_EOL . PHP_EOL . "You have " . number_format($keys) . " " . $box->getTier() . " keys available and " . $empty . " inventory slots available."));

		$max = 0;
		if($empty >= $keys){
			$max = $keys;
		}elseif($empty <= $keys){
			$max = $empty;
		}else{
			$max = $keys;
		}
		if($max === 0){
			$this->addElement(new Label(TextFormat::RED . "Your inventory may be full?"));
			return;
		}
		$this->addElement(new Slider("How many keys?", 1, $max));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$requested = $response[1] ?? 0;
		if($requested === 0) return;
		$session = $player->getGameSession()->getMysteryBoxes();
		$keys = $session->getKeys($this->box->getTier());
		if($keys < $requested){
			$player->sendMessage(TextFormat::RED . "You do not have enough keys to perform this action!");
			return;
		}
		$this->box->openMultiple($player, (int) $requested);
	}

}