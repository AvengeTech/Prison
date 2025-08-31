<?php namespace prison\gangs\battle;

use prison\gangs\objects\{
	Gang,
	TrophyData
};

class BattleStats{

	public $battle;

	public $recent = false;
	public $allies = false;

	public $finished = 0;

	public function __construct(Battle $battle, bool $recent = false, bool $allies = false){
		$this->battle = $battle;

		$this->recent = $recent;
		$this->allies = $allies;

		$this->finished = time();
	}

	public function getBattle() : Battle{
		return $this->battle;
	}

	public function hasRecentlyBattled() : bool{
		return $this->recent;
	}

	public function areAllies() : bool{
		return $this->allies;
	}

	public function getFinishedTime() : int{
		return $this->finished;
	}

	public function getKills(Gang $gang) : int{
		$battle = $this->getBattle();
		$kills = 0;
		$ppl = array_merge(
			$battle->getParticipantsFrom($gang),
			$battle->getEliminatedFrom($gang)
		);
		foreach($ppl as $pp){
			$kills += $pp->getKills();
		}
		return $kills;
	}

	public function getDeaths(Gang $gang) : int{
		$battle = $this->getBattle();
		$deaths = 0;
		$ppl = array_merge(
			$battle->getParticipantsFrom($gang),
			$battle->getEliminatedFrom($gang)
		);
		foreach($ppl as $pp){
			$deaths += $pp->getDeaths();
		}
		return $deaths;
	}

	public function getTrophiesEarned(Gang $gang) : int{
		$battle = $this->getBattle();
		if($this->hasRecentlyBattled() || $this->areAllies() || $battle->isDraw()) return 0;
		$trophies = min(TrophyData::MAX_BATTLE_KILL, $this->getKills($gang) * TrophyData::EVENT_BATTLE_KILL);
		if($battle->isWinner($gang)){
			$trophies += TrophyData::EVENT_BATTLE_WIN;
		}else{
			$trophies -= TrophyData::EVENT_BATTLE_LOSE;
		}
		return $trophies;
	}

}