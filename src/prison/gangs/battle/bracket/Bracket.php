<?php namespace prison\gangs\battle\bracket;

use prison\gangs\battle\arena\Arena;

class Bracket{

	const PHASE_WAIT = 0;
	const PHASE_START = 1;
	const PHASE_END = 2;

	public $set;

	public $entries;
	public $arena = null;

	public $spectators = [];

	public $started = false;
	public $phase = self::PHASE_WAIT;
	public $ticks = -1;

	public $winner = null;

	public function __construct(Set $set, array $entries){
		$this->set = $set;
		$this->entries = $entries;
	}

	public function tick() : void{
		switch($this->getPhase()){
			case self::PHASE_WAIT:
			case self::PHASE_END;
				break;
			case self::PHASE_START:
				foreach($this->getEntries() as $entry){
					if($entry->isOut()){
						$this->setWinner($this->getOppositeEntry($entry));
						$this->setPhase(self::PHASE_END);
						$this->ticks = -1;
					}
				}
				break;
		}
	}

	public function getSet() : Set{
		return $this->set;
	}

	public function getEntries() : array{
		return $this->entries;
	}

	public function getOppositeEntry(BracketEntry $entry) : ?BracketEntry{
		foreach($this->getEntries() as $e){
			if($e->getGang() != $entry->getGang()) return $entry;
		}
		return null;
	}

	public function getArena() : ?Arena{
		return $this->arena;
	}

	public function setArena(?Arena $arena = null) : void{
		$half = 1;
		foreach($this->getGangs() as $gang){
			$arena->getHalf($half)->setGang($gang);
			$half++;
		}
		$this->arena = $arena;
	}

	public function getGangs() : array{
		return $this->set->getGangs();
	}

	public function getSpectators() : array{
		return $this->spectators;
	}

	public function hasStarted() : bool{
		return $this->started;
	}

	public function setStarted(bool $started = true) : void{
		$this->started = $started;
	}

	public function getPhase() : int{
		return $this->phase;
	}

	public function setPhase(int $phase = self::PHASE_WAIT) : void{
		$this->phase = $phase;
	}

	public function getTicks() : int{
		return $this->ticks;
	}

	public function hasWinner() : bool{
		return $this->winner !== null;
	}

	public function setWinner(?BracketEntry $entry = null) : void{
		$this->winner = $entry;
	}

	public function getWinner() : ?BracketEntry{
		return $this->winner;
	}

}