<?php namespace prison\mines\task;

use core\utils\BlockRegistry;
use core\utils\conversion\LegacyBlockIds;
use pocketmine\block\Block;
use pocketmine\block\utils\DyeColor;
use pocketmine\item\StringToItemParser;
use pocketmine\world\format\{
	Chunk,
	io\FastChunkSerializer
};
use pocketmine\world\World;
use pocketmine\math\Vector3;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

use prison\Prison;

class AsyncMineResetTask extends AsyncTask{

	private $name;
	private $chunks;
	private $a;
	private $b;
	private $ratioData;
	private $worldId;
	private $chunkClass;

	public function __construct(string $name, array $chunks, Vector3 $a, Vector3 $b, array $data, $worldId, $chunkClass){
		$this->name = $name;
		$this->chunks = serialize($chunks);
		$this->a = serialize($a);
		$this->b = serialize($b);
		$this->ratioData = serialize($data);
		$this->worldId = $worldId;
		$this->chunkClass = $chunkClass;
	}

	public function onRun() : void{
		$cornerA = unserialize($this->a);
		$cornerB = unserialize($this->b);

		$chunkClass = $this->chunkClass;
		/** @var  Chunk[] $chunks */
		$chunks = unserialize($this->chunks);
		foreach($chunks as $hash => $binary){
			/** @var string $binary */
			$chunks[$hash] = FastChunkSerializer::deserializeTerrain($binary);
		}

		$sum = [];
		$id = array_keys(unserialize($this->ratioData));
		$m = array_values(unserialize($this->ratioData));
		$sum[0] = $m[0];

		for ($l = 1; $l < count($m); $l++) $sum[$l] = $sum[$l - 1] + $m[$l];
		$totalBlocks = ($cornerB->x - $cornerA->x + 1)*($cornerB->y - $cornerA->y + 1)*($cornerB->z - $cornerA->z + 1);
		$interval = $totalBlocks / 8; //TODO determine the interval programmatically
		$lastUpdate = 0;
		$currentBlocks = 0;
		for ($x = $cornerA->getX(); $x <= $cornerB->getX(); $x++) {
			for ($y = $cornerA->getY(); $y <= $cornerB->getY(); $y++) {
				for ($z = $cornerA->getZ(); $z <= $cornerB->getZ(); $z++) {
					$a = rand(0, end($sum));
					for ($l = 0; $l < count($sum); $l++) {
						if ($a <= $sum[$l]) {
							$hash = World::chunkHash($x >> 4, $z >> 4);
							if(isset($chunks[$hash])){
								$chunks[$hash]->setBlockStateId($x & 0x0f, $y & 0x7f, $z & 0x0f, str_replace('StateID', '', $id[$l]));
								$currentBlocks++;

								if($lastUpdate + $interval <= $currentBlocks){
									$lastUpdate = $currentBlocks;
								}
							}
							$l = count($sum);
						}
					}
				}
			}
		}
		$this->setResult(serialize($chunks));
	}

	public function onCompletion() : void{
		/** @var Chunk[] */
		$chunks = unserialize($this->getResult());
		$plugin = Prison::getInstance();
		$world = Server::getInstance()->getWorldManager()->getWorld($this->worldId);
		if($world instanceof World){
			foreach($chunks as $hash => $chunk){
				World::getXZ($hash, $x, $z);
				$world->setChunk($x, $z, $chunk);
			}
		}
		//$server->getLogger()->info("Mine ".$this->name." reset.");
		$plugin->getMines()->getMineByName($this->name)->resetting = false;
	}

}