<?php namespace prison\guards;

use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\{
	CompoundTag,
};
use pocketmine\entity\{
	EntityDataHelper,
	EntityFactory,
	Location,
	Skin
};
use pocketmine\world\World;

use prison\Prison;
use prison\guards\commands\{
	GuardPathCommand,
	GuardSpawnCommand,
	BinCommand
};
use prison\guards\entity\{
	Guard
};

class Guards{

	public PathManager $pathManager;

	public function __construct(public Prison $plugin){
		$this->pathManager = new PathManager($this);

		EntityFactory::getInstance()->register(Guard::class, function(World $world, CompoundTag $nbt) : Guard{
			return new Guard(EntityDataHelper::parseLocation($nbt, $world), Guard::parseSkinNBT($nbt));
		}, ["Guard"]);

		foreach($this->getPathManager()->getPaths() as $path){
			if($path->doesLoop() && !stristr($path->getName(), "test")){
				$this->spawnGuard($path);
			}
		}

		$plugin->getInstance()->getServer()->getCommandMap()->registerAll("guards", [
			new GuardPathCommand($plugin, "gpa", "Guard path (no touchy)"),
			new GuardSpawnCommand($plugin, "gsp", "Guard Spawn (no touchy)"),
			new BinCommand($plugin, "bin", "Open your bin to collect confiscated items from the Guard!"),
		]);
	}

	public function getPathManager() : PathManager{
		return $this->pathManager;
	}

	public function getWorld() : ?World{
		return Server::getInstance()->getWorldManager()->getWorldByName(Prison::SPAWN_LEVEL);
	}

	public function spawnGuard(?Path $path = null, ?Vector3 $pos = null) : bool{
		$world = $this->getWorld();
		if($world !== null){
			if($path !== null){
				$point = $path->getStartingPoint()->getStart();
			}elseif($pos !== null){
				$point = $pos;
			}else{
				return false;
			}

			$chunk = $world->getChunk($point->getFloorX() >> 4, $point->getFloorZ() >> 4);
			if($chunk === null){
				$world->loadChunk($point->getFloorX() >> 4, $point->getFloorZ() >> 4);
			}

			$guard = new Guard(new Location($point->x, $point->y, $point->z, $world, 0, 0), new Skin("Standard_Custom", file_get_contents("/[REDACTED]/skins/guard.dat")));
			$guard->spawnToAll();
			$guard->setPath($path);
		}
		return false;
	}
}