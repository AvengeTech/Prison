<?php namespace prison\gangs\uis\battle;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\Gang;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class BattleStatsUi extends SimpleForm{

	public $gang;
	public $stats = [];

	public function __construct(Player $player, Gang $gang, string $message = "", bool $error = true){
		parent::__construct(
			"Recent Battle Stats",
			($message != "" ? ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"Tap a battle that you'd like to see stats for below!"
		);

		$this->gang = $gang;
		foreach($gang->getBattleStatManager()->getRecentBattleStats() as $stats){
			$this->stats[] = $stats;
			$battle = $stats->getBattle();
			$this->addButton(new Button($battle->getGang1()->getName() . " vs. " . $battle->getGang2()->getName() . PHP_EOL . "Tap for more information!"));
		}
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getGangByGang($this->gang);
		if(!$gang->inGang($player)){
			$player->sendMessage(TextFormat::RI . "You are no longer in this gang!");
			return;
		}

		$stats = $this->stats[$response] ?? null;
		if($stats !== null){
			$player->showModal(new BattleResultsUi($player, $gang, $stats));
			return;
		}
	}

}