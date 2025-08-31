<?php namespace prison\gangs\battle;

use pocketmine\Server;
use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\Gang;

use core\user\User;

class BattleParticipant{

	public User $user;

	public array $original_effects = [];
	public array $original_inventory = [];
	public array $original_armor_inventory = [];

	public int $kills = 0;
	public int $deaths = 0;

	public int $lives = 0;

	public function __construct(Player $player, public Gang $gang, public Battle $battle) {
		/** @var PrisonPlayer $player */
		$this->user = $player->getUser();
		$this->lives = $battle->getRespawns();
	}

	public function getUser() : User{
		return $this->user;
	}

	public function getPlayer() : ?Player{
		return $this->getUser()->getPlayer();
	}

	public function getXuid() : int{
		return $this->getUser()->getXuid();
	}

	public function getName() : string{
		return $this->getUser()->getGamertag();
	}

	public function saveInventories(bool $clear = true) : void{
		$player = $this->getPlayer();
		$this->original_effects = $player->getEffects()->all();
		$this->original_inventory = array_merge($player->getInventory()->getContents(), $player->getCursorInventory()->getContents());
		$this->original_armor_inventory = $player->getArmorInventory()->getContents();

		if($clear){
			$player->getEffects()->clear();
			$player->getInventory()->clearAll();
			$player->getArmorInventory()->clearAll();
			$player->getCursorInventory()->clearAll();

			Prison::getInstance()->getEnchantments()->calculateCache($player);
		}
	}

	public function getGang() : Gang{
		return $this->gang;
	}

	public function getBattle() : Battle{
		return $this->battle;
	}

	public function restoreInventory() : void {
		/** @var PrisonPlayer $player */
		$player = $this->getPlayer();
		if($player instanceof Player){
			$player->stopBleeding();

			$player->getEffects()->clear();
			foreach($this->original_effects as $effect){
				$player->getEffects()->add($effect);
			}
			
			$player->setHealth(20);
			$player->getHungerManager()->setFood(20);
			$player->getHungerManager()->setSaturation(20);
			
			$player->getInventory()->clearAll();
			$player->getArmorInventory()->clearAll();
			$player->getCursorInventory()->clearAll();
			
			$player->getInventory()->setContents($this->original_inventory);
			$player->getArmorInventory()->setContents($this->original_armor_inventory);

			Prison::getInstance()->getEnchantments()->calculateCache($player);
		}else{
			//inbox maybe?
		}
	}

	public function getKills() : int{
		return $this->kills;
	}

	public function addKill() : void{
		$this->kills++;
	}

	public function getDeaths() : int{
		return $this->deaths;
	}

	public function addDeath() : void{
		$this->deaths++;
	}

	public function takeLive() : bool{
		$this->addDeath();
		$this->lives--;
		return $this->lives > 0;
	}

	public function getLives() : int{
		return $this->lives;
	}

}