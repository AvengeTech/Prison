<?php namespace prison\leaderboards\types;

use prison\Prison;

use core\Core;
use core\session\mysqli\data\{
	MySqlQuery,
	MySqlRequest
};
use core\utils\TextFormat;

class KothMonthlyWinsLeaderboard extends Leaderboard implements MysqlUpdate{

	public function getType() : string{
		return "koth_monthly_wins";
	}

	public function calculate() : void{
		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("update_leaderboard_" . $this->getType(), new MySqlQuery(
			"main",
			"SELECT xuid, monthly_wins FROM koth_stats ORDER BY monthly_wins DESC LIMIT " . $this->getSize() . ";",
			[]
		)), function(MySqlRequest $request) : void{
			$rows = $request->getQuery()->getResult()->getRows();
			$xuids = [];
			foreach($rows as $row){
				$xuids[] = $row["xuid"];
			}
			Core::getInstance()->getUserPool()->useUsers($xuids, function(array $users) use($rows) : void{
				$texts = [TextFormat::BOLD . "[RESETS EVERY MONTH!]" . PHP_EOL . TextFormat::YELLOW . TextFormat::BOLD . TextFormat::EMOJI_TROPHY . " KOTH wins (Monthly) " . TextFormat::EMOJI_TROPHY];
				$i = 1;
				foreach($rows as $row){
					$texts[($gt = $users[$row["xuid"]]->getGamertag())] =
						TextFormat::RED . $i . ". " .
						TextFormat::YELLOW . $gt . " " . TextFormat::GRAY . "- " .
						TextFormat::AQUA . number_format((int) $row["monthly_wins"]);
					$i++;
				}
				$this->texts = $texts;
				$this->updateSpawnedTo();
			});
		});
	}

}