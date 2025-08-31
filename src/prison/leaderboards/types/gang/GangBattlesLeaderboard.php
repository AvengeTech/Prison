<?php namespace prison\leaderboards\types\gang;

use prison\Prison;
use prison\leaderboards\types\{
	Leaderboard,
	MysqlUpdate
};

use core\session\mysqli\data\{
	MySqlQuery,
	MySqlRequest
};
use core\utils\TextFormat;
class GangBattlesLeaderboard extends Leaderboard implements MysqlUpdate{

	public function getType() : string{
		return "gang_battles";
	}

	public function calculate() : void{
		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("update_leaderboard_" . $this->getType(), new MySqlQuery(
			"main",
			"SELECT gang_base_data.name, gang_battle_data.wins FROM gang_base_data LEFT JOIN
				gang_battle_data ON gang_base_data.id = gang_battle_data.id
				ORDER BY gang_battle_data.wins DESC LIMIT " . $this->getSize() . ";",
			[]
		)), function(MySqlRequest $request) : void{
			$rows = $request->getQuery()->getResult()->getRows();
			$texts = [TextFormat::RED . TextFormat::BOLD . TextFormat::ICON_ARMOR . " Most Gang Battles Won " . TextFormat::ICON_ARMOR];
			$i = 1;
			foreach($rows as $row){
				$texts[] =
					TextFormat::RED . $i . ". " .
					TextFormat::YELLOW . $row["name"] . " " . TextFormat::GRAY . "- " .
					TextFormat::AQUA . number_format((int) $row["wins"]);
				$i++;
			}
			$this->texts = $texts;
			$this->updateSpawnedTo();
		});
	}

}