<?php namespace prison\blocktournament;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;

use core\user\User;

class PlayerScore{

	public User $user;

	public int $blocksMined = 0;
	public array $mineDetails = [];

	public function __construct(Player $player, public Game $game){
		/** @var PrisonPlayer $player */
		$this->user = $player->getUser();
	}
	
	public function getUser() : User{
		return $this->user;
	}

	public function getName() : string{
		return $this->getUser()->getGamertag();
	}

	public function getXuid() : int{
		return $this->getUser()->getXuid();
	}

	public function getGame() : Game{
		return $this->game;
	}

	public function getPlayer() : ?Player{
		return $this->getUser()->getPlayer();
	}

	public function getBlocksMined(string $mine = "none") : int{
		if($mine == "none")
			return $this->blocksMined;

		return $this->mineDetails[$mine] ?? 0;
	}

	public function addBlockMined(string $mine = "a") : int{
		$this->blocksMined++;

		if(!isset($this->mineDetails[$mine]))
			$this->mineDetails[$mine] = 0;
		$this->mineDetails[$mine]++;
		return ++$this->mineDetails[$mine];
	}

	public function getMineDetails() : array{
		return $this->mineDetails;
	}

	public function getPlace(bool $sort = false) : int{
		return Prison::getInstance()->getBlockTournament()->getGameManager()->getGameFrom($this->getGame())->getPlace($this->getXuid(), $sort);
	}

	public function getFormattedPlace() : string{
		$place = $this->getPlace();

		$ends = [
			"th",
			"st", "nd", "rd",
			"th", "th", "th", "th", "th", "th"
		];
		if(($place % 100) >= 11 && ($place % 100) <= 13){
        		return $place . "th";
		}
	        return $place . $ends[$place % 10];
	}

}