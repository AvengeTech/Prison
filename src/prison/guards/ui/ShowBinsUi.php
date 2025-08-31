<?php namespace prison\guards\ui;

use pocketmine\player\Player;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

use prison\PrisonPlayer;

class ShowBinsUi extends SimpleForm{

	public $bins = [];

	public function __construct(Player $player, string $message = "", bool $error = true){
		/** @var PrisonPlayer $player */
		parent::__construct(
			"Lost and Found Bin",
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"Select a bin to view it's information!"
		);
		$bins = $player->getGameSession()->getGuards()->getBins();
		foreach($bins as $bin){
			$this->bins[] = $bin;
			$this->addButton(new Button(($bin->isPaid() ? TextFormat::GREEN : TextFormat::RED) . "Bin (" . $bin->getTimeFormatted() . ")" . TextFormat::DARK_GRAY . TextFormat::ITALIC . PHP_EOL . "Tap to view!"));
		}
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$bin = $this->bins[$response] ?? null;
		if($bin !== null){
			$player->showModal(new BinUi($player, $bin));
		}
	}

}