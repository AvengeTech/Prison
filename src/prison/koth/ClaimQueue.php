<?php namespace prison\koth;

use pocketmine\Server;
use pocketmine\player\Player;

use core\utils\TextFormat;
use prison\PrisonPlayer;

class ClaimQueue{

	const COLOR_WHITE = 0;
	const COLOR_YELLOW = 4;
	const COLOR_RED = 14;

	public array $players = [];

	public function __construct(public Game $game){}

	public function tick() : void{
		$game = $this->getGame();

		foreach($game->getPlayers() as $player){
			if(
				$this->game->inCenter($player) && 
				!$player->isVanished() && 
				!$player->isTransferring() && 
				$player->isLoaded()
			){
				if(!$this->inPlayers($player)){
					if(!($koth = $player->getGameSession()->getKoth())->hasCooldown()){
						$this->addPlayer($player);
					}else{
						$player->sendTip(TextFormat::RED . "You are on KOTH cooldown!" . PHP_EOL . $koth->getFormattedCooldown());
					}
				}
			}else{
				if($this->inPlayers($player)) $this->removePlayer($player->getName());
			}
		}
		foreach($this->getPlayers() as $name => $claim){
			if(!($pl = $claim->getPlayer()) instanceof PrisonPlayer || !$game->inCenter($pl) || $pl->isVanished()){
				$this->removePlayer($name);
			}
		}

		$first = $this->getFirstPlayer();
		if($first !== null){
			$game->setGlassColor(self::COLOR_YELLOW);
			if($first->tick()){
				$this->getGame()->reward($first->getPlayer());
				foreach(Server::getInstance()->getOnlinePlayers() as $player){
					$player->sendMessage(TextFormat::GI . TextFormat::YELLOW . $first->getName() . TextFormat::LIGHT_PURPLE . " won the " . TextFormat::AQUA . $this->getGame()->getName() . TextFormat::LIGHT_PURPLE . " KOTH event! " . TextFormat::BOLD . TextFormat::GREEN . "GG");
				}
				return;
			}
		}else{
			$game->setGlassColor(self::COLOR_WHITE);
		}

		$game->updateScoreboardLines();

		foreach($this->getPlayers() as $name => $claim){
			if($claim !== $first){
				$player = $claim->getPlayer();
				$player->sendTip(TextFormat::RED . "Claiming: " . $first->getName());
			}
		}
	}

	public function getGame() : Game{
		return $this->game;
	}

	public function getPlayers() : array{
		return $this->players;
	}

	public function addPlayer(Player $player) : void{
		$this->players[$player->getName()] = new PlayerClaim($player);
	}

	public function removePlayer(string $name) : void{
		unset($this->players[$name]);
	}

	public function inPlayers(Player $player) : bool{
		return isset($this->players[$player->getName()]);
	}

	public function getFirstPlayer() : ?PlayerClaim{
		foreach($this->getPlayers() as $name => $claim){
			return $claim;
		}
		return null;
	}

	public function reset() : void{
		$this->players = [];
	}

}