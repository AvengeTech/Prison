<?php namespace prison\mines;

use pocketmine\math\Vector3;
use pocketmine\world\{
	format\io\FastChunkSerializer,
	World,
	Position,
	format\Chunk
};
use pocketmine\{
	Server,
	player\Player
};

use prison\mines\task\AsyncMineResetTask;
use prison\PrisonPlayer;

use core\utils\TextFormat;
use pocketmine\block\Block;
use pocketmine\block\tile\Container;
use pocketmine\block\utils\DyeColor;
use pocketmine\item\StringToItemParser;

class Mine{

	const MINING_RANGE = 5;
	const WALKING_RANGE = 35;
	const RESET_COOLDOWN = 180;

	public $name;

	public $reset;
	public $resetting = false;

	public $spawn;
	public $corner1;
	public $corner2;
	public $world;

	public $blockData;

	public $inCycle = false;
	public $percentEmpty = 0;

	public $totalBlocks = 0;
	public $totalMined = 0;

	public $lastReset = 0;

	public function __construct(string $name, int $reset, Vector3 $spawn, Vector3 $corner1, Vector3 $corner2, World $world, array $blocks){
		$this->name = $name;

		$this->reset = ($reset * 60);

		$this->spawn = new Position($spawn->x, $spawn->y, $spawn->z, $world);

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

	public function getName() : string{
		return $this->name;
	}

	public function getDisplayName() : string{
		if($this->getName() == "vip") return "VIP Mine";
		return "Mine " . strtoupper($this->getName());
	}

	public function getReset() : int{
		return $this->reset;
	}

	public function isResetting() : bool{
		return $this->resetting;
	}

	public function getSpawn() : Position{
		return $this->spawn;
	}

	public function getFirstCorner() : Vector3{
		return $this->corner1;
	}

	public function getSecondCorner() : Vector3{
		return $this->corner2;
	}

	public function getWorld() : ?World{
		return Server::getInstance()->getWorldManager()->getWorldByName($this->world);
	}

	public function getBlockData() : array{
		return $this->blockData;
	}

	public function inCycle() : bool{
		return $this->inCycle;
	}

	public function pvp() : bool{
		return false;
	}

	//// NON VARIABLE ////
	public function calculateTotalBlocks() : void{
		$count = 0;
		for($x = $this->getFirstCorner()->getX(); $x <= $this->getSecondCorner()->getX(); $x++){
			for($y = $this->getFirstCorner()->getY(); $y <= $this->getSecondCorner()->getY(); $y++){
				for($z = $this->getFirstCorner()->getZ(); $z <= $this->getSecondCorner()->getZ(); $z++){
					$count++;
				}
			}
		}
		$this->totalBlocks = $count;
	}

	public function getTotalBlocks() : int{
		return $this->totalBlocks;
	}

	public function getTotalMined() : int{
		return $this->totalMined;
	}

	public function addTotalMined(int $amount = 1, bool $checkEmpty = true) : void{
		$this->totalMined += $amount;

		if($checkEmpty){
			$percent = ($this->getTotalBlocks() == 0 ? 0 : round($this->getTotalMined() / $this->getTotalBlocks() * 100));
			if($percent >= 50) $this->reset();
		}
	}

	public function getLastReset() : int{
		return $this->lastReset;
	}

	public function setLastReset() : void{
		$this->lastReset = time();
	}

	public function canResetAgain() : bool{
		return $this->lastReset + self::RESET_COOLDOWN < time();
	}

	public function getTimeLeftToReset() : int{
		return $this->lastReset + self::RESET_COOLDOWN - time();
	}

	//// EXTERNALS ////
	public function teleportTo(Player $player, $reset = false) : void{
		/** @var PrisonPlayer $player */
		if (!$this->getSpawn()->isValid()) return;
		if(!$this->inCycle()){
			$this->inCycle = true;
			$this->reset();
		}
		if($player->isBattleSpectator()){
			$player->stopSpectating();
		}

		$ksession = $player->getGameSession()->getKoth();
		if($ksession->inGame()){
			$ksession->setGame();
		}

		if(!$reset){
			$player->teleport($this->getSpawn());
			return;
		}
		$player->teleport($this->getSpawn()->add(0,1, 0));
	}

	public function inMine(Position $pos) : bool{
		if($pos->getWorld()->getDisplayName() != $this->getWorld()->getDisplayName()){
			return false;
		}

		$x1 = $this->getFirstCorner()->getX();
		$x2 = $this->getSecondCorner()->getX();
		$y1 = $this->getFirstCorner()->getY();
		$y2 = $this->getSecondCorner()->getY();
		$z1 = $this->getFirstCorner()->getZ();
		$z2 = $this->getSecondCorner()->getZ();

		$x = $pos->getX();
		$y = $pos->getY();
		$z = $pos->getZ();

		return $x1 <= $x && $x <= $x2 && $y1 <= $y && $y <= $y2 && $z1 <= $z && $z <= $z2;
	}

	public function inMiningRange(Position $pos) : bool{
		if($pos->getWorld()->getDisplayName() != $this->getWorld()->getDisplayName()){
			return false;
		}

		$x1 = $this->getFirstCorner()->getX() - self::MINING_RANGE;
		$x2 = $this->getSecondCorner()->getX() + self::MINING_RANGE;
		$z1 = $this->getFirstCorner()->getZ() - self::MINING_RANGE;
		$z2 = $this->getSecondCorner()->getZ() + self::MINING_RANGE;

		$x = $pos->getX();
		$y = $pos->getY();
		$z = $pos->getZ();

		return $x1 <= $x && $x <= $x2 && $z1 <= $z && $z <= $z2;
	}

	public function inWalkingRange(Position $pos) : bool{
		if($pos->getWorld()->getDisplayName() != $this->getWorld()->getDisplayName()){
			return false;
		}

		$x1 = $this->getFirstCorner()->getX() - self::WALKING_RANGE;
		$x2 = $this->getSecondCorner()->getX() + self::WALKING_RANGE;
		$z1 = $this->getFirstCorner()->getZ() - self::WALKING_RANGE;
		$z2 = $this->getSecondCorner()->getZ() + self::WALKING_RANGE;

		$x = $pos->getX();
		$y = $pos->getY();
		$z = $pos->getZ();

		return $x1 <= $x && $x <= $x2 && $z1 <= $z && $z <= $z2;
	}

	public function reset() : void{
		$this->percentEmpty = 0;
		$this->totalMined = 0;

		$this->setLastReset();

		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			if($this->inMine($player->getPosition())){
				$this->teleportTo($player, true);
				$player->sendMessage(TextFormat::YI . "You were in a resetting mine! Luckily, we saved you before you were crushed...");
			}
		}

		$chunks = [];
		$chunkClass = Chunk::class;
		for($x = $this->getFirstCorner()->getX(); $x - 16 <= $this->getSecondCorner()->getX(); $x += 16){
			for($z = $this->getFirstCorner()->getZ(); $z - 16 <= $this->getSecondCorner()->getZ(); $z += 16) {
				$chunk = $this->getWorld()->getChunk($x >> 4, $z >> 4);
				if($chunk === null){
					$chunk = $this->getWorld()->loadChunk($x >> 4, $z >> 4);
				}
				$chunkClass = get_class($chunk);
				$chunks[World::chunkHash($x >> 4, $z >> 4)] = FastChunkSerializer::serializeTerrain($chunk);
			}
		}

		$blockData = [];
		$id = array_keys($this->getBlockData());
		$m = array_values($this->getBlockData());
		for($i = 0; $i < count($id); $i++){
			$blockId = $id[$i];

			if(str_contains($id[$i], ':')){
				$blockId = explode(":", $id[$i]);

				if(in_array($blockId[0], ['concrete', 'stained_clay']) && $blockId[1] === 'X'){
					$colorTypes = [];

					foreach(DyeColor::getAll() as $color){
						$colorTypes[] = strtolower($color->name());
					}

					$blockId = $colorTypes[array_rand($colorTypes)] . '_' . $blockId[0];
				}
			}

			$item = StringToItemParser::getInstance()->parse($blockId);

			if(is_null($item) || !($block = $item->getBlock()) instanceof Block) continue;

			$blockData['StateID' . $block->getStateId()] = $m[$i];
		}
		
		$this->resetting = true;

		$task = new AsyncMineResetTask($this->getName(), $chunks, $this->getFirstCorner(), $this->getSecondCorner(), $blockData, $this->getWorld()->getId(), $chunkClass);
		Server::getInstance()->getAsyncPool()->submitTask($task);
	}

}