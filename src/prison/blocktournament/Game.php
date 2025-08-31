<?php namespace prison\blocktournament;

use pocketmine\{
	player\Player,
	Server
};

use prison\Prison;
use prison\PrisonPlayer;
use prison\PrisonSession;

use core\Core;
use core\scoreboards\ScoreboardObject;
use core\user\User;
use core\utils\TextFormat;

class Game{

	const START_MINIMUM = 2;

	const DISPLAY_TEXT = 0;
	const DISPLAY_NUMERAL = 1;

	const GAME_OVER = 0;
	const GAME_PREPARE = 1;
	const GAME_START = 2;

	const PRIZES = [
		25000,
		50000,
		75000,
		100000,
		200000,
		250000,
		500000,
		750000,
		1000000,
		2500000,
		5000000,
		10000000,
		25000000
	];

	const TIMES = [
		300,
		600,
		900,
		1200
	];

	public int $id;

	public User $creator;

	public int $status = self::GAME_OVER;
	public int $timer = 0;

	public int $prize = 0;
	public int $length = 0;

	public array $players = [];

	public array $scoreboards = [];
	public array $lines = [];

	public array $invites = [];
	public bool $private = false;

	public ?User $winner = null;

	public function __construct(Player $player) {
		/** @var PrisonPlayer $player */
		$this->id = Prison::getInstance()->getBlockTournament()->getGameManager()->getNewId();
		$this->creator = $player->getUser();

		$this->lines = [
			1 => TextFormat::GRAY . "Mine the most blocks!",
			2 => " ",
			3 => TextFormat::GRAY . "Host: " . TextFormat::YELLOW . $this->getCreatorUser()->getGamertag(),
			//prize
			5 => TextFormat::GRAY . "Participants: " . TextFormat::AQUA . count($this->getPlayers()),
			6 => "   ",
			7 => TextFormat::GRAY . "Top players:",
			//top 3
			11 => "    ",

			13 => "     ",
			//status
		];
		$this->updateScoreboardLines(true, true, true, true);
	}

	public function getId() : int{
		return $this->id;
	}

	public function setup(int $prize = 2, int $length = 2, bool $isPrivate = false, array $invited = []) : void{
		$this->prize = self::PRIZES[$prize];
		$this->length = self::TIMES[$length];
		$this->private = $isPrivate;
		$this->invites = $invited;

		$this->lines[4] = TextFormat::GRAY . "Prize: " . TextFormat::AQUA . number_format($this->getPrize()) . " Techits";
		$this->updateAllScoreboards();
	}

	public function updateScoreboardLines(bool $timer = true, bool $status = false, bool $score = false, bool $participants = false) : void{
		if($score){
			$places = $this->getPlaces(3);
			$startLine = 8;
			foreach($places as $place){
				$this->lines[$startLine] = " " . TextFormat::DARK_YELLOW . $place->getFormattedPlace() . ": " . TextFormat::GOLD . $place->getName() . " (" . number_format($place->getBlocksMined()) . ")";
				$startLine++;
			}
			$space = 6;
			while($startLine <= 11){
				$this->lines[$startLine] = str_repeat(" ", $space);
				$space++;
				$startLine++;
			}
		}
		if($status){
			$this->lines[14] = TextFormat::GRAY . "Status: " . TextFormat::AQUA . $this->getStatusName();
		}
		if($timer){
			switch($this->getStatus()){
				case self::GAME_OVER:
					$time = "X";
					break;
				case self::GAME_PREPARE:
					$time = ($this->timer - time()) . " seconds";
					break;
				case self::GAME_START:
					$time = gmdate("i:s", $this->timer - time());
					break;
			}
			$this->lines[15] = TextFormat::GRAY . "Time: " . TextFormat::RED . $time;
		}
		if($participants){
			$this->lines[5] = TextFormat::GRAY . "Participants: " . TextFormat::AQUA . count($this->getPlayers());
		}
		ksort($this->lines);

		$this->updateAllScoreboards();
	}

	public function getLines() : array{
		return $this->lines;
	}

	public function getLinesFor(Player $player) : array{
		$lines = $this->getLines();
		$place = $this->getScore($player);
		if($place !== null){
			$lines[12] = TextFormat::DARK_YELLOW . $place->getFormattedPlace() . ": " . TextFormat::GOLD . $player->getName() . " (" . number_format($place->getBlocksMined()) . ")";
			ksort($lines);
		}
		return $lines;
	}

	public function getScoreboards() : array{
		return $this->scoreboards;
	}

	public function getScoreboard(Player $player) : ?ScoreboardObject{
		return $this->scoreboards[$player->getXuid()] ?? null;
	}

	public function addScoreboard(Player $player, bool $removeOld = true) : void{
		if($removeOld){
			Core::getInstance()->getScoreboards()->removeScoreboard($player, true);
		}

		$scoreboard = $this->scoreboards[$player->getXuid()] = new ScoreboardObject($player);
		$scoreboard->send($this->getLines());
	}

	public function removeScoreboard(Player $player, bool $addOld = true) : void{
		$scoreboard = $this->getScoreboard($player);
		if($scoreboard !== null){
			unset($this->scoreboards[$player->getXuid()]);
			$scoreboard->remove();
		}
		if($addOld){
			Core::getInstance()->getScoreboards()->addScoreboard($player);
		}
	}

	public function removeAllScoreboards() : void{
		foreach($this->scoreboards as $xuid => $sb){
			if(($pl = $sb->getPlayer()) instanceof Player){
				$sb->remove();
				Core::getInstance()->getScoreboards()->addScoreboard($pl);
			}
			unset($this->scoreboards[$xuid]);
		}
	}

	public function updateAllScoreboards() : void{
		foreach($this->scoreboards as $xuid => $sb){
			if($sb->getPlayer() instanceof Player) $sb->update($this->getLinesFor($sb->getPlayer()));
		}
	}

	public function tick() : bool{
		if(empty($this->players)){
			$this->end(true);
			return true;
		}

		if($this->getStatus() == self::GAME_OVER)
			return false;

		if($this->getStatus() == self::GAME_PREPARE){
			if($this->isPrivate()){
				if(count($this->getPlayers()) < self::START_MINIMUM){
					$this->timer = $this->getTimerReset(self::GAME_PREPARE);
					return false;
				}
			}

			if($this->timer - time() <= 0){
				$this->setStatus(self::GAME_START);
				foreach($this->getPlayers() as $p){
					$pl = $p->getPlayer();
					if($pl instanceof Player){
						$pl->sendMessage(TextFormat::GI . "The Block Tournament has started! You have " . TextFormat::YELLOW . $this->getFormattedLength($this->getStartingLength()) . "s" . TextFormat::GRAY . " to mine!");
					}
				}
				return true;
			}
		}

		if($this->getStatus() == self::GAME_START){
			if($this->timer - time() <= 0){
				$this->end();
				return true;
			}
			$this->sort();
		}

		foreach($this->getPlayers() as $pl){
			$player = $pl->getPlayer();
			if($player instanceof Player){
				if(Core::getInstance()->getScoreboards()->getPlayerScoreboard($player) !== null){
					Core::getInstance()->getScoreboards()->removeScoreboard($player, true);
					$this->addScoreboard($player);
				}
			}
		}
		$this->updateScoreboardLines(true, false, true);

		return false;
	}

	public function start(Player $player) : bool {
		/** @var PrisonPlayer $player */
		$player->takeTechits($this->getStartPrice());

		$player->getGameSession()->getBlockTournament()->addStarted();
		Core::getInstance()->getEntities()->getFloatingText()->getText("bt-data-1")->update($player);

		$this->addPlayer($player);

		$this->setStatus(self::GAME_PREPARE);
		if(!$this->isPrivate()){
			foreach(Server::getInstance()->getOnlinePlayers() as $pl) {
				/** @var PrisonPlayer $pl */
				if(!$pl->isLoaded()) continue;
				$ses = $pl->getGameSession()->getBlockTournament();
				if($ses->autoJoins() && !$pl->getGameSession()->getKoth()->inGame() && Prison::getInstance()->getBlockTournament()->getGameManager()->getPlayerGame($pl) == null){
					if($this->addPlayer($pl)){
						$pl->sendMessage(TextFormat::GI . "A public Block Tournament has been started! You were automatically entered.");
						$pl->sendMessage(TextFormat::GI . "Type " . TextFormat::YELLOW . "/bt details" . TextFormat::GRAY . " for more information!");
					}
				}else{
					$pl->sendMessage(TextFormat::GI . "A public Block Tournament has been started! Type " . TextFormat::YELLOW . "/bt join" . TextFormat::GRAY . " to participate!");
				}
			}
		}else{
			foreach($this->getInvites() as $invite){
				$player = $invite->getPlayer();
				if($player instanceof Player){
					$player->sendMessage(TextFormat::YI . "You have been invited to a private Block Tournament by " . TextFormat::YELLOW . $this->getCreatorName());
					$player->sendMessage(TextFormat::YI . "Type " . TextFormat::YELLOW . "/bt invites" . TextFormat::GRAY . " for more details!");
				}
			}
		}
		return true;
	}

	public function canAfford(Player $player) : bool {
		/** @var PrisonPlayer $player */
		return $player->getTechits() >= $this->getStartPrice();
	}

	public function getStartPrice() : int{
		return $this->getPrize() + ((array_search($this->getStartingLength(), self::TIMES) + 1) * ($this->isPrivate() ? 450 : 500));
	}

	public function end(bool $refund = false) : bool{
		$this->sort();
		if($refund){
			$this->refund();

			foreach($this->getPlayers() as $player) {
				/** @var PrisonPlayer $p */
				$p = $player->getPlayer();
				if($p instanceof Player){
					$session = $p->getGameSession()->getBlockTournament();
					$session->addMined($player->getBlocksMined());
					$session->setLastGame($this);

					Core::getInstance()->getEntities()->getFloatingText()->getText("bt-data-3")->update($p);

					$p->sendMessage(TextFormat::RI . "The block tournament you were participating in was cancelled!");
				}
			}
		}else{
			$winner = $this->winner = $this->players[0]->getUser();
			$wp = $winner->getPlayer();
			$bt = Prison::getInstance()->getBlockTournament();

			/** @var PrisonPlayer $wp */
			if($wp instanceof Player && $wp->isLoaded()) {
				$ws = $wp->getGameSession()->getBlockTournament();
				$ws->addWin();

				$wp->addTechits($this->getPrize());

				Core::getInstance()->getEntities()->getFloatingText()->getText("bt-data-2")->update($wp);

				$wp->sendMessage(TextFormat::GI . "You won the Block Tournament! GG! Type " . TextFormat::YELLOW . "/bt results" . TextFormat::GRAY . " to view game results!");
			}else{
				Prison::getInstance()->getSessionManager()->useSession($winner, function(PrisonSession $session) : void{
					$session->getBlockTournament()->addWin();
					$session->getTechits()->addTechits($this->getPrize());
					$session->save();
				});
			}

			foreach($this->getPlayers() as $player){
				$p = $player->getPlayer();
				/** @var PrisonPlayer $p */
				if($p instanceof Player && $p->isLoaded()){
					$session = $p->getGameSession()->getBlockTournament();
					$session->addMined($player->getBlocksMined());
					$session->setLastGame($this);

					Core::getInstance()->getEntities()->getFloatingText()->getText("bt-data-3")->update($p);

					$p->sendMessage(TextFormat::GI . "You finished the Block Tournament in " . TextFormat::YELLOW . $player->getFormattedPlace() . " place" . TextFormat::GRAY . "! Type " . TextFormat::YELLOW . "/bt results " . TextFormat::GRAY . "to view game results!");
				}else{
					Prison::getInstance()->getSessionManager()->useSession($player->getUser(), function(PrisonSession $session) use($player) : void{
						$session->getBlockTournament()->addMined($player->getBlocksMined());
						$session->getBlockTournament()->saveAsync();
					});
				}
			}
		}

		$this->setStatus(self::GAME_OVER);

		$this->removeAllScoreboards();

		return false;
	}

	public function refund() : void{
		$creator = $this->getCreatorUser();
		if($creator->validPlayer()) {
			/** @var PrisonPlayer $player */
			$player = $creator->getPlayer();
			$player->addTechits(($p = $this->getStartPrice()));

			$player->getGameSession()->getBlockTournament()->takeStarted();
			Core::getInstance()->getEntities()->getFloatingText()->getText("bt-data-1")->update($player);

			$player->sendMessage(TextFormat::YI . "You were refunded " . TextFormat::AQUA . number_format($p) . " Techits " . TextFormat::GRAY . "because your Block Tournament was cancelled.");
		}else{
			Prison::getInstance()->getSessionManager()->useSession($creator, function(PrisonSession $session) : void{
				$session->getBlockTournament()->takeStarted();
				$session->getTechits()->addTechits($this->getStartPrice());
				$session->save();
			});
		}
	}

	public function getCreatorUser() : User{
		return $this->creator;
	}

	public function isCreator(Player $player) : bool{
		return $player->getXuid() == $this->getCreatorXuid();
	}

	public function getCreatorXuid() : int{
		return $this->getCreatorUser()->getXuid();
	}

	public function getCreatorName() : string{
		return $this->getCreatorUser()->getGamertag();
	}

	public function getCreator() : ?Player{
		return $this->getCreatorUser()->getPlayer();
	}

	public function canEdit(Player $player) : bool {
		/** @var PrisonPlayer $player */
		return $this->isCreator($player) || $player->isStaff();
	}

	public function getStatus() : int{
		return $this->status;
	}

	public function getStatusName() : string{
		switch($this->getStatus()){
			case self::GAME_OVER:
				return "Game over!";
			case self::GAME_PREPARE:
				return "Preparing...";
			case self::GAME_START:
				return "Started!";
		}
		return "UNKNOWN";
	}

	public function isStarted() : bool{
		return $this->getStatus() == self::GAME_START;
	}

	public function isActive() : bool{
		return $this->getStatus() !== self::GAME_OVER;
	}

	public function setStatus(int $status = self::GAME_START, bool $timer = true) : void{
		$this->status = $status;
		if($timer) $this->timer = time() + $this->getTimerReset($status);
		$this->updateScoreboardLines(false, true);
	}

	public function getTimerReset(int $status = self::GAME_START) : int{
		switch($status){
			default:
			case self::GAME_OVER:
				return 0;
			case self::GAME_PREPARE:
				return 60;
			case self::GAME_START:
				return $this->getStartingLength();
		}
	}

	public function getPrize() : int{
		return $this->prize;
	}

	public function getStartingLength() : int{
		return $this->length;
	}

	public function getFormattedLength(int $length = -1, int $display = self::DISPLAY_TEXT) : string{
		if($length == -1) $length = $this->getStartingLength();

		$hours = floor($length / 3600);
		$minutes = floor(((int) ($length / 60)) % 60);
		$seconds = $length % 60;

		if($display == self::DISPLAY_TEXT){
			return $hours > 0 ? $hours . " hour" : ($minutes > 0 ? $minutes . " minute" : "");
		}else{
			return ($hours > 0 ? ($hours < 10 ? "0" . $hours : $hours) . ":" : "") . ($minutes > 0 ? ($minutes < 10 ? "0" . $minutes : $minutes) : "00") . ":" . ($seconds < 10 ? "0" . $seconds : $seconds);
		}
	}

	/** @return PlayerScore[] */
	public function getPlayers() : array{
		return $this->players;
	}

	public function addPlayer(Player $player, bool $sort = false) : bool{
		if($this->inCompetition($player)) return false;

		$this->players[] = new PlayerScore($player, $this);
		if($sort) $this->sort();

		$this->updateScoreboardLines(false, false, false, true);
		$this->addScoreboard($player, true);

		return true;
	}

	public function removePlayer(Player $player, bool $sort = false) : bool{
		if(!$this->inCompetition($player)) return false;

		unset($this->players[$this->getPlace($player->getXuid()) - 1]);
		if($sort) $this->sort();

		$this->updateScoreboardLines(false, false, false, true);
		$this->removeScoreboard($player);

		return true;
	}

	public function inCompetition(Player $player) : bool{
		foreach($this->getPlayers() as $key => $ps){
			if($ps->getXuid() == $player->getXuid()) return true;
		}
		return false;
	}

	public function getScore(Player $player) : ?PlayerScore{
		foreach($this->getPlayers() as $key => $ps){
			if($ps->getXuid() == $player->getXuid()) return $ps;
		}
		return null;
	}

	public function getUpdatedScore(PlayerScore $score) : ?PlayerScore{
		$player = $score->getPlayer();
		if($player instanceof Player){
			$score = $this->getScore($player);
		}
		return $score;
	}

	public function getPlace(int $xuid, bool $sort = false) : int{
		if($sort) $this->sort();
		foreach($this->getPlayers() as $key => $player)
			if($player->getXuid() == $xuid) return $key + 1;

		return 0;
	}

	public function getPlaces(int $amount = 10, int $page = 1, bool $sort = false) : array{
		if($sort) $this->sort();
		return array_slice($this->getPlayers(), max(0, ($amount * ($page - 1)) - 1), $amount);
	}

	public function sort() : void{
		usort($this->players, function($a, $b){
			if($a->getBlocksMined() == $b->getBlocksMined()) return 0;
			return $a->getBlocksMined() > $b->getBlocksMined() ? -1 : 1;
		});

		foreach($this->getPlayers() as $pl){
			$player = $pl->getPlayer();
			if($player instanceof Player){
				foreach([
					"bt-lb-1", "bt-lb-2",
					"bt-lb-3", "bt-lb-4",
					"bt-lb-5", "bt-lb-6",
					"bt-lb-7", "bt-lb-8",
					"bt-lb-9", "bt-lb-10"
				] as $text){
					Core::getInstance()->getEntities()->getFloatingText()->getText($text)->update($player);
				}
			}
		}
	}

	public function getWinner() : ?User{
		return $this->winner;
	}

	public function setInvites(array $invites) : void{
		$this->invites = $invites;
	}

	public function getInvites() : array{
		return $this->invites;
	}

	public function isInvited(Player $player) : bool{
		foreach($this->getInvites() as $invite)
			if($player->getXuid() == $invite->getXuid()) return true;

		return false;
	}

	public function addInvite(User|Player $user) : bool{
		foreach($this->invites as $key => $invite){
			if($invite->getXuid() == $user->getXuid()) return false;
		}
		$this->invites[] = $user;
		return true;
	}

	public function removeInvite(User|Player $user) : bool{
		foreach($this->invites as $key => $invite){
			if($invite->getXuid() == $user->getXuid()){
				unset($this->invites[$key]);
				return true;
			}
		}
		return false;
	}

	public function isPrivate() : bool{
		return $this->private;
	}

	public function getFormattedPrizes() : array{
		$prizes = self::PRIZES;
		foreach($prizes as $key => $prize){
			$prizes[$key] = number_format($prize) . " Techits";
		}
		return $prizes;
	}

	public function getFormattedLengths() : array{
		$times = self::TIMES;
		foreach($times as $key => $time){
			$times[$key] = $this->getFormattedLength($time);
		}
		return $times;
	}

}