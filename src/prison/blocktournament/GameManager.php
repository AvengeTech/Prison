<?php namespace prison\blocktournament;

use pocketmine\player\Player;

use core\utils\TextFormat;

class GameManager{

	public static $gameId = 0;

	public $active = [];
	public $inactive = [];

	public $ticks = 0;

	public function getNewId() : int{
		return self::$gameId++;
	}

	public function tick() : void{
		$this->ticks++;
		foreach($this->getActiveGames() as $key => $game){
			$game->tick();
			if(
				$game->isPrivate() &&
				$this->ticks % 60 == 0 && $game->getStatus() == Game::GAME_PREPARE
			){
				foreach($game->getInvites() as $invite){
					$player = $invite->getPlayer();
					if($player instanceof Player && $this->getPlayerGame($player) !== null){
						$player->sendMessage(TextFormat::GI . "You have been invited to a private Block Tournament. Type " . TextFormat::YELLOW . "/bt invites" . TextFormat::GRAY . " to view your invites!");
					}
				}
			}elseif(!$game->isActive()){
				$this->shift($key);
			}
		}
	}

	public function getActiveGames() : array{
		return $this->active;
	}

	public function addActiveGame(Game $game) : Game{
		return $this->active[$game->getId()] = $game;
	}

	public function getInactiveGames() : array{
		return $this->inactive;
	}

	public function getAllGames() : array{
		return array_merge($this->getActiveGames(), $this->getInactiveGames());
	}

	public function getPublicGame() : ?Game{
		foreach($this->getActiveGames() as $game){
			if(!$game->isPrivate()) return $game;
		}
		return null;
	}

	public function getGameFrom(Game $game) : ?Game{
		foreach($this->getAllGames() as $g){
			if($game->getId() == $g->getId()) return $game;
		}
		return null;
	}

	public function getPlayerGame(Player $player) : ?Game{
		foreach($this->getActiveGames() as $game){
			if($game->inCompetition($player) && $game->isActive()) return $game;
		}
		return null;
	}

	public function getJoinableGames(Player $player) : array{
		$games = [];
		foreach($this->getActiveGames() as $game)
			if((!$game->isPrivate()) || $game->isInvited($player)) $games[] = $game;

		return $games;
	}

	public function inGame(Player $player) : bool{
		foreach($this->getActiveGames() as $game){
			if($game->inCompetition($player)) return true;
		}
		return false;
	}

	public function shift(int $gameid) : void{
		$game = $this->active[$gameid] ?? null;
		if($game !== null){
			unset($this->active[$gameid]);
			$this->inactive[$gameid] = $game;
		}
	}

}