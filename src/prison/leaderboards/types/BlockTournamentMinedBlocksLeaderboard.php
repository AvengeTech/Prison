<?php namespace prison\leaderboards\types;

use prison\Prison;

use core\Core;
use core\session\mysqli\data\{
	MySqlQuery,
	MySqlRequest
};
use core\utils\TextFormat;

class BlockTournamentMinedBlocksLeaderboard extends Leaderboard implements MysqlUpdate{

	public function getType() : string{
		return "bt_mined";
	}

	public function calculate() : void{
		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("update_leaderboard_" . $this->getType(), new MySqlQuery(
			"main",
			"SELECT xuid, mined FROM block_tournament_data ORDER BY mined DESC LIMIT " . $this->getSize() . ";",
			[]
		)), function(MySqlRequest $request) : void{
			$rows = $request->getQuery()->getResult()->getRows();
			$xuids = [];
			foreach($rows as $row){
				$xuids[] = $row["xuid"];
			}
			Core::getInstance()->getUserPool()->useUsers($xuids, function(array $users) use($rows) : void{
				$texts = [TextFormat::AQUA . TextFormat::BOLD . TextFormat::ICON_ARMOR . " Most BT Mined Blocks " . TextFormat::ICON_ARMOR];
				$i = 1;
				foreach($rows as $row){
					$texts[] =
						TextFormat::RED . $i . ". " .
						TextFormat::YELLOW . $users[$row["xuid"]]->getGamertag() . " " . TextFormat::GRAY . "- " .
						TextFormat::AQUA . number_format((int) $row["mined"]);
					$i++;
				}
				$this->texts = $texts;
				$this->updateSpawnedTo();
			});
		});
	}

}