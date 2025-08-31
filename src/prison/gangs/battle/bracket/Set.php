<?php namespace prison\gangs\battle\bracket;

use pocketmine\player\Player;
use pocketmine\world\Position;

use core\utils\TextFormat;

use prison\gangs\objects\Gang;
use prison\Prison;

class Set{

	const TYPE_SMALL = 0;
	const TYPE_NORMAL = 1;
	const TYPE_LARGE = 2;
	const TYPE_OFFICIAL = 3;

	const MINIMUM_PARTICIPANTS = 1; //todo: change 4 release

	const PHASE_PRESTART = -1;
	const PHASE_COUNTDOWN = 0;
	const PHASE_START = 1;
	const PHASE_NEXT_COUNTDOWN = 2;
	const PHASE_END = 3;

	public $gangs = [];
	public $type = self::TYPE_SMALL;

	public $entries = [];

	public $brackets = [];

	public $phase = self::PHASE_PRESTART;
	public $ticks = -1;
	public $round = 0;

	public $public = false;

	public function __construct(array $gangs, int $type = self::TYPE_SMALL){
		$this->gangs = $gangs;
		$this->type = $type;
	}

	public function tick_redo() : void{
		switch($this->getPhase()){

		}
	}

	public function tick() : void{
		switch($this->getPhase()){
			case self::PHASE_PRESTART:
				if(count($this->getEntries()) >= $this->getNeededGangs()){
					$start = true;
					$needfinish = [];
					foreach($this->getEntries() as $entry){
						if(count($entry->getParticipants()) < self::MINIMUM_PARTICIPANTS){
							$start = false;
							$needfinish[] = $entry;
						}
					}
					if(!$start){
						foreach($this->getEntries() as $entry){
							foreach($entry->getParticipants() as $pa){
								$player = $pa->getPlayer();
								if($player instanceof Player){
									$player->sendTip("War: Waiting for " . count($needfinish) . " entries...");
								}else{
									$entry->removeParticipant($pa->getXuid());
									$gang = $entry->getGang();
									foreach($gang->getMemberManager()->getOnlineMembers() as $mem){
										$mem->getPlayer()->sendMessage(TextFormat::RI . TextFormat::YELLOW . $pa->getName() . TextFormat::GRAY . " has left the server, removing them from the war!");
									}
								}
							}
						}
						return;
					}
					$this->setPhase(self::PHASE_COUNTDOWN);
					$this->ticks = 10;
					$this->setupBrackets();
					foreach($this->getBrackets() as $bracket){
						foreach($bracket->getEntries() as $en){
							$op = $bracket->getOppositeEntry($en);
							foreach($en->getParticipants() as $p){
								$pl = $p->getPlayer();
								$pl->sendMessage(TextFormat::GI . "Gang war is starting soon! You are fighting against gang " . TextFormat::YELLOW . $op->getName());
							}
						}
					}
					return;
				}
				$need = $this->getNeededGangs() - count($this->getEntries());
				foreach($this->getEntries() as $entry){
					foreach($entry->getParticipants() as $pa){
						$player = $pa->getPlayer();
						if($player instanceof Player){
							$player->sendTip("War: Waiting for " . $need . " gangs to enter...");
						}else{
							$entry->removeParticipant($pa->getXuid());
							$gang = $entry->getGang();
							foreach($gang->getMemberManager()->getOnlineMembers() as $mem){
								$mem->getPlayer()->sendMessage(TextFormat::RI . TextFormat::YELLOW . $pa->getName() . TextFormat::GRAY . " has left the server, removing them from the war!");
							}
						}
					}
				}
				break;

			case self::PHASE_COUNTDOWN:
				$ticks = $this->ticks;
				$starting = true;
				foreach($this->getEntries() as $entry){
					foreach($entry->getParticipants() as $pa){
						$player = $pa->getPlayer();
						if($player instanceof Player){
							$player->sendTip("Countdown: Starting war in " . $ticks . " seconds...");
						}else{
							$entry->removeParticipant($pa->getXuid());
							$gang = $entry->getGang();
							foreach($gang->getMemberManager()->getOnlineMembers() as $mem){
								$mem->getPlayer()->sendMessage(TextFormat::RI . TextFormat::YELLOW . $pa->getName() . TextFormat::GRAY . " has left the server, removing them from the war!");
							}
						}
					}
					if(count($entry->getParticipants()) < self::MINIMUM_PARTICIPANTS){
						$this->setPhase(self::PHASE_PRESTART);
						$starting = false;
					}
				}
				if(!$starting){
					foreach($this->getEntries() as $entry){
						foreach($entry->getParticipants() as $pa){
							$player = $pa->getPlayer();
							$player->sendMessage("War could not be started! A player has left");
						}
					}
					return;
				}
				$this->ticks--;
				if($this->ticks <= 0){
					$this->setPhase(self::PHASE_START);
					$this->round = 1;
					$this->ticks = 300; //5 min test

					foreach($this->getBrackets() as $bracket){
						$arena = $bracket->getArena();
						foreach($bracket->getEntries() as $entry){
							$half = $arena->getHalfByGang($entry->getGang());
							foreach($entry->getParticipants() as $pl){
								($player = $pl->getPlayer())->teleport(Position::fromObject($half->getSpawn(), $half->getLevel()));
							}
						}
					}
				}
				break;

			case self::PHASE_START:
				$need = $this->getNeededGangs() - count($this->getEntries());
				foreach($this->getBrackets() as $bracket){
					foreach($bracket->getEntries() as $entry){
						foreach($entry->getParticipants() as $pa){
							$player = $pa->getPlayer();
							if($player instanceof Player){
								$player->sendTip("War: Waiting for " . $need . " gangs to enter...");
							}else{
								$entry->removeParticipant($pa->getXuid());
								$gang = $entry->getGang();
								foreach($gang->getMemberManager()->getOnlineMembers() as $mem){
									$mem->getPlayer()->sendMessage(TextFormat::RI . TextFormat::YELLOW . $pa->getName() . TextFormat::GRAY . " has left the server, removing them from the war!");
								}
							}
						}
					}
				}

				$done = true;
				$left = 0;
				foreach($this->getBrackets() as $bracket){
					if(!$bracket->hasWinner()){
						$done = false;
						$left++;
					}
				}
				if(!$done){
					
				}
				break;
			case self::PHASE_NEXT_COUNTDOWN:
			case self::PHASE_END:
				break;
		}
	}

	public function getGangs() : array{
		return $this->gangs;
	}

	public function getNeededGangs() : int{
		switch($this->getType()){
			default:
			case self::TYPE_SMALL:
				return 4;
			case self::TYPE_NORMAL:
				return 8;
			case self::TYPE_LARGE:
			case self::TYPE_OFFICIAL:
				return 16;
		}
	}

	public function getType() : int{
		return $this->type;
	}

	public function setType(int $type = self::TYPE_SMALL) : void{
		$this->type = $type;
	}

	public function inSet(Gang $gang) : bool{
		foreach($this->getGangs() as $g){
			if($g->getId() == $gang->getId()) return true;
		}
		return false;
	}

	public function getEntries() : array{
		return $this->entries;
	}

	public function addEntry(BracketEntry $entry) : bool{
		if(!$this->inSet($entry->getGang())) return false;
		if(count($entry->getParticipants()) < self::MINIMUM_PARTICIPANTS) return false;
		$this->entries = $entry;
	}

	public function getInEntries() : array{
		$e = [];
		foreach($this->getEntries() as $entry){
			if(!$entry->isOut()) $e[] = $entry;
		}
		return $e;
	}

	public function getBrackets() : array{
		return $this->brackets;
	}

	public function setupBrackets() : void{
		$brackets = [];
		$entries = [];
		foreach($this->getEntries() as $entry){
			$entries[] = $entry;
			if(count($entries) >= 2){
				$brackets[] = new Bracket($this, ...$entries);
				$entries = [];
			}
		}

		$arenasused = 0;
		foreach($brackets as $bracket){
			$bracket->setArena(Prison::getInstance()->getGangs()->getGangManager()->getBattleManager()->getArena($arenasused));
			$arenasused++;
		}

		$this->brackets = $brackets;
	}

	public function resortBrackets() : void{
		$brackets = [];
		$entries = [];

		foreach($this->getBrackets() as $bracket){
			foreach($bracket->getEntries() as $entry){
				if(!$entry->isOut()){
					$entries[] = $entry;
					break;
				}
			}
		}

		$e = [];
		foreach($entries as $entry){
			$gang = $entry->getGang();
			foreach($entry->getParticipants() as $p){
				$player = $p->getPlayer();
				if($player instanceof Player){
					if($p->isDead()){
						$p->setDead(false);
						$player->sendMessage(TextFormat::GI . "You're back in the next round! Good luck!");
					}
				}else{
					$entry->removeParticipant($p->getXuid());
				}
			}
			$e[] = $entry;
			if(count($e) >= 2){
				$brackets[] = new Bracket($this, ...$e);
				$e = [];
			}
		}
		foreach($brackets as $bracket){
			foreach($bracket->getEntries() as $entry){
				if(count($entry->getAliveParticipants()) <= 0){
					$entry->setOut(true); //todo; set winner?
				}
			}
		}
		$this->brackets = $brackets;
	}

	public function getPhase() : int{
		return $this->phase;
	}

	public function setPhase(int $phase) : void{
		$this->phase = $phase;
	}

	public function getTicks() : int{
		return $this->ticks;
	}

	public function getRound() : int{
		return $this->round;
	}

	public function setRound(int $round) : void{
		$this->round = $round;
	}

	public function isPublic() : bool{
		return $this->public;
	}

	public function setPublic(bool $public = true) : void{
		$this->public = $public;
	}

}