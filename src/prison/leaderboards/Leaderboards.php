<?php namespace prison\leaderboards;

use pocketmine\entity\Location;
use pocketmine\player\Player;

use prison\Prison;
use prison\leaderboards\command\Prizes;
use prison\leaderboards\cycle\StatCycle;
use prison\leaderboards\types\{
	VoteStreakLeaderboard,

	PvPKillsLeaderboard,
	MineKillsLeaderboard,
	GrinderKillsLeaderboard,
	PvPKdrLeaderboard,
	MineKdrLeaderboard,

	PrestigeLeaderboard,
	MinedBlocksLeaderboard,
	TechitsLeaderboard,
	BlockTournamentWinsLeaderboard,
	BlockTournamentMinedBlocksLeaderboard,

	PvPDeathsLeaderboard,
	MineDeathsLeaderboard,

	KeysLeaderboard,
	KeysOpenedLeaderboard,

	KothAllTimeWinsLeaderboard,
	KothAllTimeKillsLeaderboard,
	KothMonthlyWinsLeaderboard,
	KothMonthlyKillsLeaderboard,
	MineMonthlyKillsLeaderboard,

	gang\GangTrophiesLeaderboard,
	gang\GangBattlesLeaderboard,
	gang\GangKillsLeaderboard,
	gang\GangBlocksLeaderboard,
	gang\GangBankLeaderboard,

	MysqlUpdate
};
use prison\PrisonPlayer;
use prison\leaderboards\ui\LeaderboardPrizesUi;

use core\utils\entity\Trophy;

class Leaderboards{

	const UPDATE_TICKS = 600;

	public int $ticks = 0;
	public array $toUpdate = [];

	public array $leaderboards = [];

	public array $left = [];

	public function __construct(public Prison $plugin){
		$plugin->getServer()->getCommandMap()->registerAll("leaderboards", [
			new Prizes($plugin, "lbprizes", "View weekly/monthly leaderboard prizes!"),
		]);

		$this->leaderboards["vote_streak"] = new VoteStreakLeaderboard();

		$this->leaderboards["pvp_kills"] = new PvPKillsLeaderboard();
		$this->leaderboards["mine_kills"] = new MineKillsLeaderboard();
		$this->leaderboards["grinder_kills"] = new GrinderKillsLeaderboard();
		$this->leaderboards["pvp_kdr"] = new PvPKdrLeaderboard();
		$this->leaderboards["mine_kdr"] = new MineKdrLeaderboard();

		$this->leaderboards["prestige"] = new PrestigeLeaderboard();
		$this->leaderboards["mined_blocks"] = new MinedBlocksLeaderboard();
		$this->leaderboards["techits"] = new TechitsLeaderboard();
		$this->leaderboards["bt_wins"] = new BlockTournamentWinsLeaderboard();
		$this->leaderboards["bt_mined"] = new BlockTournamentMinedBlocksLeaderboard();

		$this->leaderboards["gang_trophies"] = new GangTrophiesLeaderboard();
		$this->leaderboards["gang_battles"] = new GangBattlesLeaderboard();
		$this->leaderboards["gang_kills"] = new GangKillsLeaderboard();
		$this->leaderboards["gang_blocks"] = new GangBlocksLeaderboard();
		$this->leaderboards["gang_bank"] = new GangBankLeaderboard();

		$this->leaderboards["pvp_deaths"] = new PvPDeathsLeaderboard();
		$this->leaderboards["mine_deaths"] = new MineDeathsLeaderboard();

		$this->leaderboards["keys"] = new KeysLeaderboard();
		$this->leaderboards["keys_opened"] = new KeysOpenedLeaderboard();

		$this->leaderboards["koth_alltime_wins"] = new KothAllTimeWinsLeaderboard();
		$this->leaderboards["koth_alltime_kills"] = new KothAllTimeKillsLeaderboard();
		$this->leaderboards["koth_monthly_wins"] = new KothMonthlyWinsLeaderboard();
		$this->leaderboards["koth_monthly_kills"] = new KothMonthlyKillsLeaderboard();
		$this->leaderboards["mine_monthly_kills"] = new MineMonthlyKillsLeaderboard();

		foreach($this->getLeaderboards() as $lb){
			if($lb instanceof MysqlUpdate){
				$lb->calculate();
			}
		}

		$this->spawnTrophies();

		new StatCycle();
	}

	public function getLeaderboards() : array{
		return $this->leaderboards;
	}

	public function spawnTrophies() : void{
		$world = $this->plugin->getServer()->getWorldManager()->getDefaultWorld();

		$pos = [
			[
				"x" => -773.5,
				"y" => 27.95,
				"z" => 344.5,
				"func" => function(Player $player) : void{
					/** @var PrisonPlayer $player */
					$player->showModal(new LeaderboardPrizesUi());
				}
			],
		];
		if($world !== null){ //double check
			foreach($pos as $key => $xyz){
				$chunk = $world->getChunk((int) $xyz["x"] >> 4, (int) $xyz["z"] >> 4);
				if($chunk === null){
					$world->loadChunk((int) $xyz["x"] >> 4, (int) $xyz["z"] >> 4);
				}

				$trophy = new Trophy(new Location($xyz["x"], $xyz["y"], $xyz["z"], $world, 140, 0), null, $xyz["func"] ?? null);
				$trophy->spawnToAll();
			}

		}
	}

	public function tick() : void{
		$this->ticks++;
		if($this->ticks >= self::UPDATE_TICKS){
			$this->ticks = 0;
			foreach($this->getLeaderboards() as $key => $leaderboard){
				if($leaderboard instanceof MysqlUpdate){
					$this->toUpdate[] = $key;
				}
			}
		}

		if(!empty($this->toUpdate)){
			$this->leaderboards[array_shift($this->toUpdate)]->calculate();
		}
	}

	public function changeLevel(Player $player, string $newlevel) : void{
		foreach($this->leaderboards as $leaderboard){
			$leaderboard->changeLevel($player, $newlevel);
		}
	}

	public function onJoin(Player $player) : void{
		unset($this->left[$player->getName()]);
		foreach($this->leaderboards as $leaderboard){
			if(!$leaderboard instanceof MysqlUpdate) $leaderboard->calculate();
			$leaderboard->spawn($player);
		}
	}

	public function onQuit(Player $player) : void{
		$this->left[$player->getName()] = true;
		foreach($this->leaderboards as $leaderboard){
			$leaderboard->despawn($player);
			if($leaderboard->isOn($player) && $leaderboard instanceof MysqlUpdate) $leaderboard->calculate();
		}
	}

}