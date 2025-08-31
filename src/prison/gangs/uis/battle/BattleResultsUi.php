<?php namespace prison\gangs\uis\battle;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\{
	Gang,
	TrophyData
};
use prison\gangs\battle\BattleStats;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class BattleResultsUi extends SimpleForm{

	public $gang;

	public function __construct(Player $player, Gang $gang, BattleStats $stats){
		$this->gang = $gang;

		$recent = $stats->hasRecentlyBattled();
		$allied = $stats->areAllies();

		$battle = $stats->getBattle();
		$draw = $battle->isDraw();

		$p1 = array_merge(
			$battle->getParticipantsFrom($gang1 = $battle->getGang1()),
			$battle->getEliminatedFrom($gang1)
		);
		$p1k = $stats->getKills($gang1);
		$p1d = $stats->getDeaths($gang1);

		$p2 = array_merge(
			$battle->getParticipantsFrom($gang2 = $battle->getGang2()),
			$battle->getEliminatedFrom($gang2)
		);
		$p2k = $stats->getKills($gang2);
		$p2d = $stats->getDeaths($gang2);

		$t = $stats->getTrophiesEarned($gang);
		$winner = $battle->isWinner($gang);

		parent::__construct(
			$gang1->getName() . " vs. " . $gang2->getName(),
			($allied ?
				"Your gang is allied with this gang, meaning neither gang earned any trophies or stats!" :
				($draw ? 
					"This gang battle has ended in a draw, meaning neither gang earned any trophies!" : 
					"Your gang has " . ($winner ? "won" : "lost") . " this battle" .
					($winner ?
						" and earned a total of " . TextFormat::GOLD . $t . " trophies" :
						" and lost " . TextFormat::GOLD . ($recent ? "0" : TrophyData::EVENT_BATTLE_LOSE) . " trophies"
					)
				)
			) . TextFormat::WHITE . ($recent ? " (Recently battled)" : "") . PHP_EOL . PHP_EOL . 

			"Your gang can earn " . TextFormat::GOLD . TrophyData::EVENT_BATTLE_KILL . " trophies " . TextFormat::WHITE . "per battle kill (" . TrophyData::MAX_BATTLE_KILL . " max) and " . TextFormat::GOLD . TrophyData::EVENT_BATTLE_WIN . " trophies " . TextFormat::WHITE . "per win!" . PHP_EOL . PHP_EOL .

			"Trophies are only earned if your gang wins a battle." . PHP_EOL . PHP_EOL .

			"View the stats of each individual player below!"
		);

		$this->addButton(new Button(TextFormat::BOLD . TextFormat::RED . $gang1->getName()));
		foreach($p1 as $pp){
			$this->addButton(new Button(TextFormat::RED . $pp->getName() . PHP_EOL . TextFormat::DARK_RED . "(Kills: " . $pp->getKills() . " / Deaths: " . $pp->getDeaths() . ")"));
		}

		$this->addButton(new Button(TextFormat::BOLD . TextFormat::BLUE . $gang2->getName()));
		foreach($p2 as $pp){
			$this->addButton(new Button(TextFormat::BLUE . $pp->getName() . PHP_EOL . TextFormat::DARK_BLUE . "(Kills: " . $pp->getKills() . " / Deaths: " . $pp->getDeaths() . ")"));
		}
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getGangByGang($this->gang);
		if(!$gang->inGang($player)){
			$player->sendMessage(TextFormat::RI . "You are no longer in this gang!");
			return;
		}

		$player->showModal(new BattleStatsUi($player, $gang));
	}

}