<?php namespace prison\guards\entity;

use core\utils\conversion\LegacyBlockIds;
use pocketmine\entity\{animation\ArmSwingAnimation, Human, Location, Skin};
use pocketmine\block\{Block, Slab, Stair, VanillaBlocks};
use pocketmine\world\{
	World,
	Position,

	ChunkLoader,
	format\Chunk,

	particle\SmokeParticle,
	sound\EndermanTeleportSound
};
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\event\entity\{
	EntityDamageEvent,
	EntityDamageByEntityEvent
};
use pocketmine\{
	player\Player,
	Server
};

use pocketmine\item\{
	Sword,
	VanillaItems
};
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use prison\enchantments\Calls;
use prison\Prison;
use prison\guards\Path;
use prison\PrisonPlayer;

class Guard extends Human implements ChunkLoader{

	const DIALOGUE = [
		"casual" => [
			"Hey there {player}!",
			"I love this job",
			"If you see anything suspicious, report it to me!",
		],
		"warn" => [
			"I'm warning you! Put that weapon away now!",
			"Keep your weapon away from me, you wouldn't like when I'm angry..",
			"Hey buddy.. Watch your sword, or things will get ugly fast.",
		],
		"attack" => [
			"That's it! You leave me no choice!",
			"I warned you! You messed with the wrong guard buddy!",
			"Take this!",
			"You don't know how to listen do you?",
		],
		"lesson" => [
			"Don't mess with me next time!",
			"Maybe that'll teach you a lesson.. Coward!",
			"I hope you learned your lesson, know your place!",
		],
	];

	const WARNING_TIME = 100;

	const TP_PHASE_NONE = -1;
	const TP_PHASE_CHARGE = 0;
	const TP_PHASE_TELEPORT = 1;

	const FIND_DISTANCE = 10;
	const LOSE_DISTANCE = 15;

	const MODE_PATH = 0;
	const MODE_LOOK = 1;
	const MODE_ATTACK = 2;

	public $spawnTicks = 10;

	public $aliveTicks = 0;
	public $jumpTicks = 0;

	public $mode = self::MODE_PATH;

	public $path;

	public $lookingAt = "";
	public $lookingTicks = 0;
	public $lookTime = 0;

	public $tpPhase = self::TP_PHASE_NONE;
	public $tpTicks = -1;
	public $tpPos = null;
	public $tpDespawn = false;

	public $warnings = [];

	public $loaderId = 0;
	public $lastChunkHash;
	public $loadedChunks = [];

	public function __construct(Location $level, Skin $nbt){
		parent::__construct($level, $nbt);

		$this->loaderId = $this->getId();

		$this->setNametag(TextFormat::GOLD . TextFormat::BOLD . "Guard");
		$this->setMaxHealth(10000); $this->setHealth(10000);

		$this->getWorld()->addSound($this->getPosition(), new EndermanTeleportSound());
		$this->spawnTicks = 10;
	}

	public function getRandomDialogue(string $type = "casual") : string{
		$dialogue = self::DIALOGUE[$type] ?? [];
		if(empty($dialogue)) return "";
		return $dialogue[array_rand($dialogue)];
	}

	public function getMode() : int{
		return $this->mode;
	}

	public function setMode(int $mode, ?Player $target = null, int $lookTime = 100) : void{
		$this->mode = $mode;
		switch($mode){
			case self::MODE_PATH:
				$this->getInventory()->setItemInHand(VanillaBlocks::AIR()->asItem());
				break;
			case self::MODE_LOOK:
				if(!$target instanceof Player){
					$this->findLookingAt();
				}else{
					$this->setLookingAt($target);
				}
				$this->lookTime = $lookTime;
				break;
			case self::MODE_ATTACK:
				if($target === null){
					$this->setMode(self::MODE_LOOK, null, 20);
					return;
				}
				$target->sendMessage(TextFormat::GOLD . TextFormat::BOLD . "Guard: " . TextFormat::RESET . TextFormat::GRAY . str_replace("{player}", $target->getName(), $this->getRandomDialogue("attack")));
				$this->getInventory()->setItemInHand(VanillaItems::DIAMOND_SWORD());
				break;
		}
	}

	public function hasPath() : bool{
		return $this->getPath() !== null;
	}

	public function getPath() : ?Path{
		return $this->path;
	}

	public function setPath(?Path $path = null, bool $start = true, bool $teleport = false) : void{
		$this->path = $path;
		if($path !== null){
			if($start) $this->getPath()->setStarted(true);
			if($teleport) $this->initTp(Position::fromObject($path->getStartingPoint()->getStart(), $this->getWorld()), false);
		}
	}

	public function getLookingAt() : ?PrisonPlayer{
		return Server::getInstance()->getPlayerExact($this->lookingAt);
	}

	public function setLookingAt(?Player $player = null) : void{
		$this->lookingAt = $player instanceof Player ? $player->getName() : "";
	}

	public function hasLookingAt() : bool{
		return
			($la = $this->getLookingAt()) !== null && 
			$la->getPosition()->distance($this->getPosition()) <= self::LOSE_DISTANCE && !$la->isVanished();
	}

	public function findLookingAt() : bool{
		$this->lookingTicks = 0;
		/** @var PrisonPlayer $nearest */
		$nearest = $this->getWorld()->getNearestEntity($this->getPosition(), self::FIND_DISTANCE, Player::class);
		if($nearest !== null && !$nearest->isVanished()){
			$this->lookingAt = $nearest->getName();
			return true;
		}
		return false;
	}

	public function hasWarning(Player $player) : bool{
		return isset($this->warnings[$player->getName()]);
	}

	public function warn(Player $player) : void{
		$this->warnings[$player->getName()] = self::WARNING_TIME;
	}

	public function getTpPhase() : int{
		return $this->tpPhase;
	}

	public function initTp(Position $pos, bool $stopPath = true, bool $despawn = true) : void{
		$this->tpPhase = self::TP_PHASE_CHARGE;
		$this->setTpPos($pos);
		$this->tpTicks = 15;

		if($this->hasPath() && $stopPath){
			$this->getPath()->setStarted(false);
		}

		$this->getInventory()->setItemInHand(VanillaItems::SLIMEBALL());
		$this->tpDespawn = $despawn;
	}

	public function getTpPos() : ?Position{
		return $this->tpPos;
	}

	public function setTpPos(?Position $pos = null) : void{
		$this->tpPos = $pos;
	}

	public function tickTp() : bool{
		if($this->getTpPhase() == self::TP_PHASE_NONE)
			return false;

		if($this->tpTicks > 0)
			$this->tpTicks--;

		if($this->tpTicks <= 0){
			switch($this->getTpPhase()){
				case self::TP_PHASE_CHARGE:
					$this->getWorld()->addSound($this->getPosition(), new EndermanTeleportSound());

					if(!$this->tpDespawn){
						$this->teleport($this->getTpPos());
						$this->getWorld()->addSound($this->getPosition(), new EndermanTeleportSound());

						$this->tpPhase = self::TP_PHASE_TELEPORT;
						$this->tpTicks = 15;
					}else{
						$this->flagForDespawn();
					}
					return true;

				case self::TP_PHASE_TELEPORT:
					$this->tpPhase = self::TP_PHASE_NONE;
					$this->tpTicks = -1;
					$this->setTpPos();

					$this->getInventory()->setItemInHand(VanillaBlocks::AIR()->asItem());
					return true;
			}
		}else{
			for($i = 0; $i <= 3; $i++){
				$this->getWorld()->addParticle($this->getPosition()->add(
					mt_rand(-10,10)/10,
					mt_rand(0,20)/10,
					mt_rand(-10,10)/10),
					new SmokeParticle()
				);
			}
			return true;
		}

		return false;
	}

	public function attack(EntityDamageEvent $source) : void{
		$source->cancel();
		if($source instanceof EntityDamageByEntityEvent){
			$player = $source->getDamager();
			if($player instanceof Player){
				if(!in_array($this->getMode(), [self::MODE_LOOK, self::MODE_ATTACK])){
					$this->setMode(self::MODE_LOOK, $player);
				}
				$hand = $player->getInventory()->getItemInHand();
				if($hand instanceof Sword){
					if($this->hasWarning($player)){
						$this->warn($player);
						if($this->getMode() !== self::MODE_ATTACK){
							$this->setMode(self::MODE_ATTACK, $player);
						}
						Calls::getInstance()->hitAs($this, $player, mt_rand(5, 75));
						$this->broadcastAnimation(new ArmSwingAnimation($this));
					}else{
						$this->getInventory()->setItemInHand(VanillaItems::DIAMOND_SWORD());
						$dialogue = $this->getRandomDialogue("warn");
						$player->sendMessage(TextFormat::GOLD . TextFormat::BOLD . "Guard: " . TextFormat::RESET . TextFormat::GRAY . str_replace("{player}", $player->getName(), $dialogue));
						$this->warn($player);
					}
				}elseif($this->getMode() !== self::MODE_ATTACK){
					$dialogue = $this->getRandomDialogue();
					$player->sendMessage(TextFormat::GOLD . TextFormat::BOLD . "Guard: " . TextFormat::RESET . TextFormat::GRAY . str_replace("{player}", $player->getName(), $dialogue));
				}
			}
		}
	}

	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);
		$this->setCanSaveWithChunk(false);

		$this->getWorld()->registerChunkLoader($this, $this->getPosition()->getFloorX() >> 4, $this->getPosition()->getFloorZ() >> 4);
		$this->lastChunkHash = World::chunkHash($this->getPosition()->getFloorX() >> 4, $this->getPosition()->getFloorZ() >> 4);
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->isFlaggedForDespawn()) return false;

		if($this->spawnTicks > 0){
			$this->spawnTicks--;
			for($i = 0; $i <= 3; $i++){
				$this->getWorld()->addParticle($this->getPosition()->add(
					mt_rand(-10,10)/10,
					mt_rand(0,20)/10,
					mt_rand(-10,10)/10),

					new SmokeParticle()
				);
			}
		}

		$this->aliveTicks++;

		if($this->jumpTicks > 0){
			$this->jumpTicks--;
		}

		foreach($this->warnings as $name => $ticks){
			$ticks--;
			if($ticks < 0){
				unset($this->warnings[$name]);
			}else{
				$this->warnings[$name] = $ticks;
			}
		}

		if($this->tickTp())
			return $this->isAlive();


		switch($this->getMode()){
			case self::MODE_PATH:
				if($this->hasPath()){
					$path = $this->getPath();
					if($path->isStarted()){
						if($path->atPointEnd($this->getPosition())){
							if(($np = $path->getNextPoint()) == null){
								//$this->flagForDespawn();
								//$this->setPath($rp = Prison::getInstance()->getGuards()->getPathManager()->getRandomPath(), true, true);
								$this->initTp($this->getPosition(), true, true);
								return true;
							}

							$this->getLocation()->x = ($pe = $path->getCurrentPoint()->getEnd())->x;
							$this->getLocation()->y = $pe->y;
							$this->getLocation()->z = $pe->z;

							$path->setCurrentPoint($np);
						}

						$point = $path->getCurrentPoint();
						$end = $point->getEnd();

						$x = $end->x - $this->getLocation()->x;
						$y = $end->y - $this->getLocation()->y;
						$z = $end->z - $this->getLocation()->z;

						$this->motion->x = $this->getSpeed() * 0.35 * ($x / (abs($x) + abs($z))) * $tickDiff;
						$this->motion->z = $this->getSpeed() * 0.35 * ($z / (abs($x) + abs($z))) * $tickDiff;

						$this->setRotation(rad2deg(atan2(-$x, $z)), 0);

						if($this->shouldJump()){
							$this->jump();
						}
					}
				}
				break;
			case self::MODE_LOOK:
				$this->lookingTicks++;
				if($this->lookTime > -1){
					$this->lookTime--;
					if($this->lookTime <= 0){
						$this->lookTime = -1;
						$this->setMode(self::MODE_PATH);
						return true;
					}
				}
				if($this->lookingTicks >= 600){
					$this->lookingTicks = ($this->findLookingAt() ? 0 : 300);
					return true;
				}

				if(!$this->hasLookingAt()){
					return true;
				}

				$looking = $this->getLookingAt();
				if($this->lookingTicks % 2 == 0){
					$x = $looking->getLocation()->x - $this->getLocation()->x;
					$y = $looking->getLocation()->y - $this->getLocation()->y;
					$z = $looking->getLocation()->z - $this->getLocation()->z;
					$this->setRotation(rad2deg(atan2(-$x, $z)), rad2deg(-atan2($y, sqrt($x * $x + $z * $z))));
					$this->updateMovement();
				}
				break;

			case self::MODE_ATTACK:
				if(!$this->hasLookingAt()){
					$this->setMode(self::MODE_PATH);
					return true;
				}
				$this->lookingTicks++;
				$looking = $this->getLookingAt();
				if($this->lookingTicks % 2 == 0){
					$x = $looking->getLocation()->x - $this->getLocation()->x;
					$y = $looking->getLocation()->y - $this->getLocation()->y;
					$z = $looking->getLocation()->z - $this->getLocation()->z;
					$this->setRotation(rad2deg(atan2(-$x, $z)), rad2deg(-atan2($y, sqrt($x * $x + $z * $z))));
					$this->updateMovement();
				}

				if($this->getPosition()->distance($looking->getPosition()) > 6){
					$this->setMode(self::MODE_PATH);
					$looking->sendMessage(TextFormat::GOLD . TextFormat::BOLD . "Guard: " . TextFormat::RESET . TextFormat::GRAY . $this->getRandomDialogue("lesson"));
					return true;
				}
				if($this->lookingTicks % 10 == 0){
					Calls::getInstance()->hitAs($this, $looking, mt_rand(5, 75));
					$this->broadcastAnimation(new ArmSwingAnimation($this));
				}
				break;

		}

		return $this->isAlive();
	}

	public function registerToChunk(int $chunkX, int $chunkZ){
		if(!isset($this->loadedChunks[World::chunkHash($chunkX, $chunkZ)])){
			$this->loadedChunks[World::chunkHash($chunkX, $chunkZ)] = true;
			$this->getWorld()->registerChunkLoader($this, $chunkX, $chunkZ);
		}
	}

	public function unregisterFromChunk(int $chunkX, int $chunkZ){
		if(isset($this->loadedChunks[World::chunkHash($chunkX, $chunkZ)])){
			unset($this->loadedChunks[World::chunkHash($chunkX, $chunkZ)]);
			$this->getWorld()->unregisterChunkLoader($this, $chunkX, $chunkZ);
		}
	}

	public function onChunkChanged(Chunk $chunk){

	}

	public function onChunkLoaded(Chunk $chunk){

	}

	public function onChunkUnloaded(Chunk $chunk){

	}

	public function onChunkPopulated(Chunk $chunk){

	}

	public function onBlockChanged(Vector3 $block){

	}

	public function getLoaderId() : int{
		return $this->loaderId;
	}

	public function isLoaderActive() : bool{
		return !$this->isFlaggedForDespawn() && !$this->closed;
	}

	public function getFrontBlock(float $y = 0) : Block{
		$dv = $this->getDirectionVector();
		$pos = $this->getPosition()->asVector3()->floor()->add($dv->x, $y + 1, $dv->z)->round();
		return $this->getWorld()->getBlock($pos);
	}

	public function shouldJump() : bool{
		if($this->jumpTicks > 0) return false;

		return $this->isCollidedHorizontally || 
		($this->getFrontBlock()->getTypeId() != LegacyBlockIds::legacyIdToTypeId(0) || $this->getFrontBlock(-1) instanceof Stair) ||
		($this->getWorld()->getBlock($this->getPosition()->asVector3()->floor()->add(0,-0.5, 0)) instanceof Slab &&
		(!$this->getFrontBlock(-0.5) instanceof Slab && $this->getFrontBlock(-0.5)->getTypeId() != LegacyBlockIds::legacyIdToTypeId(0))) &&
		$this->getFrontBlock(1)->getTypeId() == LegacyBlockIds::legacyIdToTypeId(0) && 
		$this->getFrontBlock(2)->getTypeId() == LegacyBlockIds::legacyIdToTypeId(0) && 
		$this->jumpTicks == 0;
	}

	public function getJumpMultiplier() : float{
		return 4.5;
	}

	public function jump() : void{
		$this->motion->y = $this->gravity * $this->getJumpMultiplier();
		$this->move($this->motion->x * 1.35, $this->motion->y, $this->motion->z * 1.35);
		$this->jumpTicks = 4;
	}

	public function getSpeed() : float{
		return 1.25;
		//return 0.75;
	}

	public function canSaveWithChunk() : bool{
		return false;
	}

	public function canPickupXp() : bool{
		return true;
	}

}