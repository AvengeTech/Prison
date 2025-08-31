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

class GangBlocksLeaderboard extends Leaderboard implements MysqlUpdate{

	public function getType() : string{
		return "gang_blocks";
	}

	public function calculate() : void{
		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("update_leaderboard_" . $this->getType(), new MySqlQuery(
			"main",
			"SELECT name, blocks FROM gang_base_data ORDER BY blocks DESC LIMIT " . $this->getSize() . ";",
			[]
		)), function(MySqlRequest $request) : void{
			$rows = $request->getQuery()->getResult()->getRows();
			$texts = [TextFormat::RED . TextFormat::BOLD . TextFormat::ICON_ARMOR . " Most Gang Blocks Mined " . TextFormat::ICON_ARMOR];
			$i = 1;
			foreach($rows as $row){
				$texts[] =
					TextFormat::RED . $i . ". " .
					TextFormat::YELLOW . $row["name"] . " " . TextFormat::GRAY . "- " .
					TextFormat::AQUA . number_format((int) $row["blocks"]);
				$i++;
			}
			$this->texts = $texts;
			$this->updateSpawnedTo();
		});
	}

}