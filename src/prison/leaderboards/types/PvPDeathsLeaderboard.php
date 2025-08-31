<?php namespace prison\leaderboards\types;

use prison\Prison;

use core\Core;
use core\session\mysqli\data\{
	MySqlQuery,
	MySqlRequest
};
use core\utils\TextFormat;

class PvPDeathsLeaderboard extends Leaderboard implements MysqlUpdate{

	public function getType() : string{
		return "pvp_deaths";
	}

	public function calculate() : void{
		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("update_leaderboard_" . $this->getType(), new MySqlQuery(
			"main",
			"SELECT xuid, pvp_deaths FROM combat_stats ORDER BY pvp_deaths DESC LIMIT " . $this->getSize() . ";",
			[]
		)), function(MySqlRequest $request) : void{
			$rows = $request->getQuery()->getResult()->getRows();
			$xuids = [];
			foreach($rows as $row){
				$xuids[] = $row["xuid"];
			}
			Core::getInstance()->getUserPool()->useUsers($xuids, function(array $users) use($rows) : void{
				$texts = [TextFormat::RED . TextFormat::BOLD . TextFormat::ICON_ARMOR . " Most /PvP Deaths " . TextFormat::ICON_ARMOR];
				$i = 1;
				foreach($rows as $row){
					$texts[] =
						TextFormat::RED . $i . ". " .
						TextFormat::YELLOW . $users[$row["xuid"]]->getGamertag() . " " . TextFormat::GRAY . "- " .
						TextFormat::AQUA . number_format((int) $row["pvp_deaths"]);
					$i++;
				}
				$this->texts = $texts;
				$this->updateSpawnedTo();
			});
		});
	}

}