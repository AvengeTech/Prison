<?php namespace prison\guards\ui;

use pocketmine\player\Player;

use prison\PrisonPlayer;
use prison\guards\Bin;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use core\utils\TextFormat;

class BinUi extends SimpleForm{

	public $bin;

	public function __construct(Player $player, Bin $bin, string $message = "", bool $error = true){
		parent::__construct("Bin",
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .

			"Bin Information:" . PHP_EOL . PHP_EOL .
			"Price to access: " . TextFormat::AQUA . number_format($bin->getPrice()) . " Techits" . TextFormat::WHITE . PHP_EOL .
			"Paid: " . ($bin->isPaid() ? TextFormat::GREEN . "YES" : TextFormat::RED . "NO") . TextFormat::WHITE . PHP_EOL . PHP_EOL .

			"Select an option below!"
		);

		$this->bin = $bin;
		if(!$bin->isPaid()){
			$this->addButton(new Button("Pay for Access"));
		}
		$this->addButton(new Button("View Contents"));
		$this->addButton(new Button("Dispose of Items"));
		$this->addButton(new Button("Go back"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$bin = $this->bin;
		$session = $player->getGameSession()->getGuards();

		if(!$bin->isPaid()){
			if($response == 0){
				if($player->getTechits() < $bin->getPrice()){
					$player->showModal(new BinUi($player, $bin, "You do not have enough techits to unlock this bin!"));
					return;
				}
				$player->takeTechits($bin->getPrice());
				$bin->setPaid();
				$session->setBin($bin);
				$player->showModal(new BinUi($player, $bin, "You may now access this bin!", false));
				return;
			}
			$response--;
		}
		if($response == 0){
			$bin->open($player);
			return;
		}
		if($response == 1){
			$player->showModal(new ConfirmDeleteBinUi($player, $bin));
			return;
		}
		$player->showModal(new ShowBinsUi($player));
	}

}