<?php namespace prison\mines;

use pocketmine\block\BlockFactory;
use pocketmine\player\Player;
use pocketmine\math\Vector3;
use pocketmine\world\{
	World,
	Position
};
use pocketmine\block\Block;

use prison\Prison;
use prison\PrisonPlayer;

class PvPMine extends Mine{

	public $spawns = [];

	public function __construct(string $name, int $reset, array $spawns, Vector3 $corner1, Vector3 $corner2, World $world, array $blocks){
		$this->name = $name;
		$this->reset = ($reset * 60);

		$this->spawn = new Position($spawns[0]->x, $spawns[0]->y, $spawns[0]->z, $world);
		foreach($spawns as $spawn){
			$this->spawns[] = new Position($spawn->x, $spawn->y, $spawn->z, $world);
		}

		$minX = min($corner1->getX(), $corner2->getX());
		$maxX = max($corner1->getX(), $corner2->getX());
		$minY = min($corner1->getY(), $corner2->getY());
		$maxY = max($corner1->getY(), $corner2->getY());
		$minZ = min($corner1->getZ(), $corner2->getZ());
		$maxZ = max($corner1->getZ(), $corner2->getZ());

		$this->corner1 = new Vector3($minX, $minY, $minZ);
		$this->corner2 = new Vector3($maxX, $maxY, $maxZ);

		$this->world = $world->getDisplayName();

		$this->blockData = $blocks;

		$this->calculateTotalBlocks();
	}

	public function getDisplayName() : string{
		return "PvP Mine";
	}

	public function pvp() : bool{
		return true;
	}

	public function getSpawn() : Position{
		return $this->getRandomSpawn();
	}

	public function getSpawns() : array{
		return $this->spawns;
	}

	public function getRandomSpawn() : Position{
		$spawns = $this->getSpawns();
		return $spawns[mt_rand(0, count($spawns) - 1)];
	}

	public function teleportTo(Player $player, $reset = false) : void{
		/** @var PrisonPlayer $player */
		parent::teleportTo($player, $reset);
		if(($gs = $player->getGameSession())->getMines()->getMine() !== $this){
			$gs->getCombat()->setInvincible();
		}
	}

}