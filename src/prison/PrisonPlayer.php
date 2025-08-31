<?php namespace prison;

use pocketmine\Server;
use pocketmine\block\{
	BlockTypeIds,
	VanillaBlocks
};
use pocketmine\entity\{
	Entity,
    Location,
    Skin
};
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\{
    LegacySkinAdapter,
    SkinAdapterSingleton,
	TypeConverter
};
use pocketmine\network\mcpe\protocol\{
	PlayerFogPacket,
	types\entity\EntityMetadataCollection,
	types\entity\EntityMetadataFlags,
	types\entity\EntityMetadataProperties,
	types\entity\PropertySyncData,
	types\inventory\ItemStackWrapper,
	AddPlayerPacket,
	UpdateAbilitiesPacket,
	types\AbilitiesData,
	PlayerListPacket,
	types\PlayerListEntry,
	SetActorDataPacket,
	MovePlayerPacket,
	RemoveActorPacket,
	SetPlayerGameTypePacket
};
use pocketmine\player\{
	GameMode,
	Player,
    PlayerInfo
};
use pocketmine\world\{
	Position,
	particle\BlockBreakParticle as DestroyBlockParticle
};

use Ramsey\Uuid\Uuid;

use prison\Prison;
use prison\gangs\battle\{
	Battle,
	BattleParticipant,
	Spectator
};
use prison\hud\HudObject;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\sound\EntityLandSound;
use pocketmine\world\sound\GhastShootSound;
use prison\inventory\PlayerArmorListener;
use prison\skills\Skill;

use core\Core;
use core\AtPlayer;
use core\network\Links;
use core\network\NetworkSession;
use core\utils\TextFormat;
use core\network\protocol\PlayerLoadActionPacket;
use core\network\server\ServerInstance;

class PrisonPlayer extends AtPlayer{

	public function __construct(Server $server, NetworkSession $session, PlayerInfo $playerInfo, bool $authenticated, Location $spawnLocation, ?CompoundTag $namedtag) {
		parent::__construct($server, $session, $playerInfo, $authenticated, $spawnLocation, $namedtag);

		$this->armorInventory->getListeners()->add(new PlayerArmorListener($this));
	}

	const DIMENSION_OVERWORLD = 0;
	const DIMENSION_NETHER = 1;
	const DIMENSION_END = 2;

	const PLOT_WORLDS = [
		"s0plots",
		"nether_plots_s0",
		"end_plots_s0",
	];

	const LEGACY_PLOTS = [
		"s3plots",
		"nether_plotsnew",
		"end_plotsnew",
		
		"plots-season1p1",
		"plots-season1p2",

		"new_plots",
		"nether_plots",
		"end_plots",

		"s4plots",
		"nether_plots_s4",
		"end_plots_s4",
	];

	public ?Player $bleedInflict = null;
	public int $bleedTicks = 0;

	public int $combo = 0;
	public int $comboTicks = -1;

	public int $gappleCooldown = 0;

	public bool $healthChanged = false;

	public bool $loadedPrison = false;
	
	public $lastBattleParticipant = null;

	public bool $changingDimension = false;
	public int $dimension = self::DIMENSION_OVERWORLD;

	public bool $pcsMode = false;

	public bool $networkPropertiesDirty = true;

	public bool $canDoubleJump = true;

	public bool $boostCheck = false;

	public function getGameSession() : ?PrisonSession{
		return Prison::getInstance()->getSessionManager()->getSession($this);
	}

	public function hasGameSession() : bool{
		return $this->getGameSession() !== null;
	}

	public function getTechits() : int{
		return $this->getGameSession()->getTechits()->getTechits();
	}

	public function setTechits(int $value) : void{
		$this->getGameSession()->getTechits()->setTechits($value);
	}

	public function takeTechits(int $value){
		$this->getGameSession()->getTechits()->takeTechits($value);
	}

	public function addTechits(int $value){
		$this->getGameSession()->getTechits()->addTechits($value);
	}

	protected function onDeath() : void{
		parent::onDeath();

		if($this->isBleeding()){
			$this->stopBleeding();
		}
	}

	public function doubleJump(): void {
		if ($this->canDoubleJump && ($this->inSpawn() || $this->inPlotWorld()) && !$this->isCreative()) {
			$this->setFlying(false);
			$this->setAllowFlight(false);
			$dv = $this->getDirectionVector();
			$this->knockback($dv->x, $dv->z, 1.5);
			if (!$this->isVanished() && $this->isLoaded()) {
				if (($cs = $this->getSession()->getCosmetics())->hasEquippedDoubleJump()) {
					$cs->getEquippedDoubleJump()->activate($this);
				} else {
					$this->getWorld()->addSound($this->getPosition(), new GhastShootSound());
				}
			}
			$this->canDoubleJump = false;
		}
	}

	protected function onHitGround(): ?float {
		$this->canDoubleJump = true;
		if ($this->inSpawn() || $this->inPlotWorld()) $this->setAllowFlight(true);

		$fallBlockPos = $this->location->floor();
		$fallBlock = $this->getWorld()->getBlock($fallBlockPos);
		if (count($fallBlock->getCollisionBoxes()) === 0) {
			$fallBlockPos = $fallBlockPos->down();
			$fallBlock = $this->getWorld()->getBlock($fallBlockPos);
		}
		$newVerticalVelocity = $fallBlock->onEntityLand($this);

		if ($fallBlock->getTypeId() !== BlockTypeIds::AIR) {
			$this->broadcastSound(new EntityLandSound($this, $fallBlock));
		}

		return $newVerticalVelocity;
	}

	public function onUpdate(int $currentTick) : bool{
		$tickDiff = ($currentTick - $this->lastUpdate);
		$hasUpdate = parent::onUpdate($currentTick);

		$combat = $this->getGameSession()?->getCombat()->isTagged() ?? false;
		$this->setScoreTag($this->getHealthBar(null, $combat));

		if ($tickDiff <= 0) return $hasUpdate;

		if ($this->inSpawn() || $this->inPlotWorld()) {
			$f = $this->isFlying() && ($this->inFlightMode() || $this->isCreative());
			$this->setFlying($f);
		}
		if (!$this->canDoubleJump && !($this->inFlightMode() || $this->isCreative())) {
			$this->setAllowFlight(false);
			$this->setFlying(false);
		}

		if($this->getCombo() > 0){
			if ($this->comboTicks > 0) {
				$this->comboTicks -= $tickDiff;
			}else{
				$this->combo = 0;
			}
		}

		if ($this->bleedTicks > 0) {
			for ($_ = 0; $_ < $tickDiff; $_++) {
				$this->bleedTicks--;
				if ($this->bleedTicks <= 0) break;
				if ($this->bleedInflict instanceof PrisonPlayer) {
					if ($this->bleedTicks % 15 == 0) {
						if (($effect = $this->effectManager->get(VanillaEffects::REGENERATION())) !== null) {
							VanillaEffects::REGENERATION()->applyEffect($this, $effect);
						}
						$damage = mt_rand(0, 2) / 2;
						if ($this->getHealth() <= $damage) {
							if ($this->bleedInflict->isAlive() && $this->bleedInflict->isConnected()) {
								Prison::getInstance()->getCombat()->processKill($this->bleedInflict, $this);
							} else {
								Prison::getInstance()->getCombat()->processSuicide($this);
							}
						} else {
							$this->setHealth($this->getHealth() - $damage);
							$this->getWorld()->addParticle($this->getPosition(), new DestroyBlockParticle(VanillaBlocks::REDSTONE()));
						}
					}
				}
			}
		} else {
			$this->bleedTicks = 0;
		}
		if ($this->bleedTicks <= 0) {
			$this->bleedInflict = null;
		}

		if ($this->isConnected() && !is_null($gs = $this->getGameSession()) && $gs->isLoaded()) {
			if ($this->boostCheck !== $gs->getShops()->isActive()) {
				$this->boostCheck = $gs->getShops()->isActive();
				if (!$this->boostCheck) {
					$this->sendMessage(TextFormat::RN . "Your sale booster has ended! You are now on a 15 minute cooldown.");
				}
			}
		}

		return $hasUpdate;
	}

	public function setBleedTicks(int $ticks): void {
		$this->bleedTicks = $ticks;
	}

	public function getHealthBar(?PrisonPlayer $for = null, bool $combat = false): string {
		$max = round($this->getMaxHealth());
		$health = round($this->getHealth());
		$absorption = round($this->getAbsorption());
		$gangTag = $this->getGangTag($for, $combat);
		$inputMode = $this->getInputMode();
		$os = $this->getDeviceOSname();
		if ($combat) return $gangTag . TextFormat::RESET . TextFormat::GREEN . $inputMode . TextFormat::AQUA . " [" . ($health >= 0 ? TextFormat::GREEN . str_repeat("|", (int) $health) : "") . (($max - $health) >= 0 ? TextFormat::RED . str_repeat("|", (int) ($max - $health)) : "") . ($absorption > 0 ? TextFormat::GOLD . str_repeat("|", (int) $absorption) : "") . TextFormat::AQUA . "]";
		return $gangTag . TextFormat::RESET . TextFormat::GREEN . $os . TextFormat::AQUA . " [" . ($health >= 0 ? TextFormat::GREEN . str_repeat("|", (int) $health) : "") . (($max - $health) >= 0 ? TextFormat::RED . str_repeat("|", (int) ($max - $health)) : "") . ($absorption > 0 ? TextFormat::GOLD . str_repeat("|", (int) $absorption) : "") . TextFormat::AQUA . "] " . TextFormat::GREEN . $inputMode;
	}

	public function attack(EntityDamageEvent $source) : void{
		parent::attack($source);
		$this->healthChanged = true;
	}

	public function setHealth(float $amount) : void{
		parent::setHealth($amount);
		if($this->isAlive()){
			$this->healthChanged = true;
		}
	}

	public function setMaxHealth(int $amount) : void{
		parent::setMaxHealth($amount);
		if($this->isAlive()){
			$this->healthChanged = true;
		}
	}

	public function getCombo() : int{
		return $this->combo;
	}

	public function addCombo() : void{
		$this->combo++;
		$this->comboTicks = 40;
	}

	public function resetCombo() : void{
		$this->combo = 0;
		$this->comboTicks = -1;
	}

	public function isLoadedP() : bool{
		return $this->loadedPrison;
	}
	
	public function canFly() : string|bool{
		if(
			Core::thisServer()->getTypeId() == "event" &&
			!$this->isTier3()
		) return "You cannot fly during an event!";

		if($this->getRankHierarchy() < 2){
			return "You must have at least " . TextFormat::ICON_BLAZE . TextFormat::GOLD . TextFormat::BOLD . "BLAZE" . TextFormat::RESET . TextFormat::GRAY . " rank to use this! Purchase a rank at " . Links::SHOP;
		}
		if((
			Prison::getInstance()->getCombat()->canCombat($this) || (
				$gs = $this->getGameSession())->getMines()->inMine() ||
				$gs->getCombat()->inPvPMode() ||
				$this->getGameSession()->getKoth()->inGame()
			) && !Prison::getInstance()->isTestServer()
		) return "You cannot fly right now.";
		return true;
	}

	public function atSpawn() : bool{
		return $this->inLobby();
	}
	
	public function setFlightMode(bool $mode = true, ?GameMode $gamemode = null, bool $doubleJumpEnabled = false) : void{
		parent::setFlightMode($mode);

		if(!$mode){
			$ms = $this->getGameSession()->getMines();
			if($ms->inMine() || $this->inPlotWorld()){
				$this->setGamemode(GameMode::SURVIVAL());
			}else{
				$this->setGamemode(GameMode::ADVENTURE());
			}
			if($this->inSpawn() || $this->inPlotWorld()) $this->setAllowFlight(true);	
		}
	}

	//Cells
	public function getCells() : array{
		return Prison::getInstance()->getCells()->getCellManager()->getPlayerCells($this);
	}

	public function getStores() : array{
		$stores = [];
		foreach($this->getCells() as $cell){
			$stores = array_merge(
				$stores,
				$cell->getStoreManager()->getPlayerStores($this)
			);
		}
		return $stores;
	}

	//Enchantment stuff
	public function isBleeding() : bool{
		return $this->bleedTicks > 0;
	}

	public function bleed(?Player $player, int $ticks){
		$this->bleedInflict = $player;
		$this->bleedTicks += $ticks;
	}

	public function stopBleeding(){
		$this->bleedInflict = null;
		$this->bleedTicks = 0;
	}

	//Convenience
	public function getMineRank() : string{
		return $this->getGameSession()->getRankUp()->getRank();
	}

	public function getPrestige() : int{
		return $this->getGameSession()->getRankUp()->getPrestige();
	}

	public function getSkill(string $identifier) : Skill{
		return $this->getGameSession()->getSkills()->getSkill($identifier);
	}

	public function removeChildEntities() : int{
		return Prison::getInstance()->getCombat()->removeChildEntities($this);
	}

	public function inSpawn() : bool{
		return $this->getWorld()->getDisplayName() == Prison::SPAWN_LEVEL;
	}

	public function gotoPvPserver(string $reason = "") : ?ServerInstance{
		if(!($ts = Core::thisServer())->isSubServer() || $ts->getSubId() !== "pvp"){
			$parent = Core::getInstance()->getNetwork()->getServerManager()->getServer("prison-" . $ts->getTypeId() . "-pvp");
			if($parent->isOnline()){
				//$parent->transfer($this, $reason);
				Prison::getInstance()->onQuit($this, true);
				if($this->isLoaded()){
					$this->getGameSession()->save(true, function($session) use ($parent, $reason) : void{
						if($this->isConnected()){
							$parent->transfer($this, $reason);
							$parent->sendSessionSavedPacket($this, 1);
						}
						$this->getGameSession()->getSessionManager()->removeSession($this);
					});
				}
				$this->sendMessage(TextFormat::YELLOW . "Saving game session data...");
				return $parent;
			}else{
				$lobby = Core::getInstance()->getNetwork()->getServerManager()->getLeastPopulated("lobby");
				if($lobby !== null && $lobby->isOnline()){
					$lobby->transfer($this, $reason);
					return $lobby;
				}else{
					//i dunno yet lol this should rarely happen
					//maybe kick?
					return null;
				}
			}
		}
		return null;
	}

	/**
	 * Plot types:
	 * 0 - Normal
	 * 1 - Nether
	 * 2 - End
	 * 3 - Sugma
	 */
	public function gotoPlotServer(int $plotType = 0, string $reason = "") : ?ServerInstance{
		if(!($ts = Core::thisServer())->isSubServer() || $ts->getSubId() !== "plots"){
			$parent = Core::getInstance()->getNetwork()->getServerManager()->getServer("prison-" . $ts->getTypeId() . "-plots");
			if($parent->isOnline()){
				//$parent->transfer($this, $reason);
				Prison::getInstance()->onQuit($this, true);
				if($this->isLoaded()){
					$this->getGameSession()->save(true, function($session) use ($parent, $reason, $plotType, $ts) : void{
						if($this->isConnected()){
							(new PlayerLoadActionPacket([
								"player" => $this->getName(),
								"server" => "prison-" . $ts->getTypeId() . "-plots",
								"action" => "plots",
								"actionData" => ["type" => $plotType]
							]))->queue();

							$parent->transfer($this, $reason);
							$parent->sendSessionSavedPacket($this, 1);
						}
						$this->getGameSession()->getSessionManager()->removeSession($this);
					});
				}
				$this->sendMessage(TextFormat::YELLOW . "Saving game session data...");
				return $parent;
			}else{
				$lobby = Core::getInstance()->getNetwork()->getServerManager()->getLeastPopulated("lobby");
				if($lobby !== null && $lobby->isOnline()){
					$lobby->transfer($this, $reason);
					return $lobby;
				}else{
					//i dunno yet lol this should rarely happen
					//maybe kick?
					return null;
				}
			}
		}else{
			switch($plotType){
				case self::DIMENSION_OVERWORLD:
					if(!Server::getInstance()->getWorldManager()->isWorldLoaded("s0plots")){
						Server::getInstance()->getWorldManager()->loadWorld("s0plots");
					}
					$this->teleport(new Position(32, 56, 32, Server::getInstance()->getWorldManager()->getWorldByName("s0plots")), 0, 0);
					break;
				case self::DIMENSION_NETHER:
					if(!Server::getInstance()->getWorldManager()->isWorldLoaded("nether_plots_s0")){
						Server::getInstance()->getWorldManager()->loadWorld("nether_plots_s0");
					}
					$this->changeDimensionTeleport(1, new Position(36.5, 56, 37.5, Server::getInstance()->getWorldManager()->getWorldByName("nether_plots_s0")), 0, 0);
					break;
				case self::DIMENSION_END:
					if(!Server::getInstance()->getWorldManager()->isWorldLoaded("end_plots_s0")){
						Server::getInstance()->getWorldManager()->loadWorld("end_plots_s0");
					}
					$this->changeDimensionTeleport(2, new Position(63.5, 57, 63.5, Server::getInstance()->getWorldManager()->getWorldByName("end_plots_s0")), 0, 0);
					break;
			}
		}
		return null;
	}
	
	public function gotoSpawn(bool $setfly = false, string $reason = "") : ?ServerInstance{
		if(($ts = Core::thisServer())->isSubServer()){
			/** @var SubServer $ts */
			$parent = $ts->getParentServer();
			if($parent->isOnline()){
				//$parent->transfer($this, $reason);
				Prison::getInstance()->onQuit($this, true);
				if($this->isLoaded()){
					$this->getGameSession()->save(true, function($session) use($parent, $reason) : void{
						if($this->isConnected()){
							if(($cm = $this->getGameSession()->getCombat())->isTagged()){
								$cm->untag();
							}
							$parent->transfer($this, $reason);
							$parent->sendSessionSavedPacket($this, 1);
						}
						$this->getGameSession()->getSessionManager()->removeSession($this);
					});
				}
				$this->sendMessage(TextFormat::YELLOW . "Saving game session data...");
				return $parent;
			}else{
				$lobby = Core::getInstance()->getNetwork()->getServerManager()->getLeastPopulated("lobby");
				if($lobby !== null && $lobby->isOnline()){
					$lobby->transfer($this, $reason);
					return $lobby;
				}else{
					//i dunno yet lol this should rarely happen
					//maybe kick?
					return null;
				}
			}
		}else{
			$this->teleport(Prison::getSpawn());
			if($setfly) $this->setAllowFlight(true);
			return null;
		}
	}

	public function getHud() : ?HudObject{
		return Prison::getInstance()->getHud()->getHud($this);
	}

	public function inPlotWorld() : bool{
		return in_array($this->getWorld()->getDisplayName(), self::PLOT_WORLDS);
	}

	public function inLegacyPlotWorld() : bool{
		return in_array($this->getWorld()->getDisplayName(), self::LEGACY_PLOTS);
	}

	public function getDimension() : int{
		return $this->dimension;
	}

	public function setDimension(int $dimension) : void{
		$this->dimension = $dimension;
	}

	public function changeDimensionTeleport(int $dimension, Position $position, ?float $yaw = null, ?float $pitch = null) : void{
		if($dimension != $this->getDimension()){
			$this->setDimension($dimension);

			/**$pk = new ChangeDimensionPacket();
			$pk->dimension = $dimension;
			$pk->position = $position->asVector3();
			$pk->respawn = false;
			$this->getNetworkSession()->sendDataPacket($pk);*/

			$this->changingDimension = true;
			$this->teleport($position, $yaw, $pitch);
			$this->changingDimension = false;

			/**$pk = new PlayStatusPacket();
			$pk->status = PlayStatusPacket::PLAYER_SPAWN;
			$this->getNetworkSession()->sendDataPacket($pk);*/

			if($dimension == self::DIMENSION_NETHER){
				$pk = PlayerFogPacket::create(["minecraft:fog_hell"]);
			}elseif($dimension == self::DIMENSION_END){
				$pk = PlayerFogPacket::create(["minecraft:fog_the_end"]);
			}else{
				$pk = PlayerFogPacket::create(["minecraft:fog_default"]);
			}
			$this->getNetworkSession()->sendDataPacket($pk);

			if(($hud = $this->getHud()) instanceof HudObject) $hud->sendDelayed();
		}else{
			$this->teleport($position, $yaw, $pitch);
		}
	}

	public function teleport(Vector3 $pos, float $yaw = null, float $pitch = null) : bool{
		if(!$this->changingDimension && ($od = $this->getDimension()) !== 0 && $pos instanceof Position && $pos->getWorld() !== $this->getWorld()){
			$this->setDimension(0);

			/**$pk = new ChangeDimensionPacket();
			$pk->dimension = 0;
			$pk->position = $pos->asVector3();
			$pk->respawn = false;
			$this->getNetworkSession()->sendDataPacket($pk);*/

			parent::teleport($pos, $yaw, $pitch);

			/**$pk = new PlayStatusPacket();
			$pk->status = PlayStatusPacket::PLAYER_SPAWN;
			$this->getNetworkSession()->sendDataPacket($pk);*/

			$pk = PlayerFogPacket::create(["minecraft:fog_default"]);
			$this->getNetworkSession()->sendDataPacket($pk);

			if(($hud = $this->getHud()) instanceof HudObject) $hud->sendDelayed();

			return true;
		}
		return parent::teleport($pos, $yaw, $pitch);
	}

	public function isBattleSpectator() : bool{
		return Prison::getInstance()->getGangs()->getGangManager()->getBattleManager()->isSpectator($this);
	}

	public function getSpectator() : ?Spectator{
		return Prison::getInstance()->getGangs()->getGangManager()->getBattleManager()->getSpectating($this);
	}

	public function isBattleParticipant() : bool{
		$gm = Prison::getInstance()->getGangs()->getGangManager();
		return
			$gm->inGang($this) &&
			($gang = $gm->getPlayerGang($this))->inBattle() &&
			$gang->getBattle()->isParticipating($this);
	}

	public function getGangBattle() : ?Battle{
		$gm = Prison::getInstance()->getGangs()->getGangManager();
		if($gm->inGang($this)){
			return $gm->getBattleManager()->getBattleByGang($gm->getPlayerGang($this));
		}
		return null;
	}

	public function stopSpectating(bool $setFly = false) : void{
		$this->getSpectator()->remove();
		if($setFly) $this->setAllowFlight(true);
	}

	public function hasGappleCooldown() : bool{
		return $this->gappleCooldown >= time();
	}

	public function getGappleCooldown() : int{
		return $this->gappleCooldown - time();
	}

	public function setGappleCooldown() : void{
		$this->gappleCooldown = time() + 45;
	}

	public function spawnTo(Player $player) : void{
		if(!$this->isBattleSpectator()){
			parent::spawnTo($player);
		}
	}

	public function despawnFrom(Player $player, bool $send = true) : void{
		parent::despawnFrom($player, $send);
	}

	public function setSneaking(bool $value = true) : void{
		parent::setSneaking($value);
	}

	public function getGangTag(?PrisonPlayer $for = null, bool $combat = false): string {
		$gangs = Prison::getInstance()->getGangs()->getGangManager();
		$gang = $gangs->getPlayerGang($this);
		$fgang = !is_null($for) ? $gangs->getPlayerGang($for) : null;
		if ($gang === null) return "";
		if (is_null($fgang)) $color = TextFormat::RED;
		else {
			if ($fgang->getId() === $gang->getId()) $color = TextFormat::GREEN;
			elseif (($fgang->isLoaded() && $gang->isLoaded()) && $fgang->getAllianceManager()->areAllies($fgang, $gang)) $color = TextFormat::AQUA;
			else $color = TextFormat::RED;
		}
		if (!isset($color)) $color = TextFormat::RED;
		if ($for?->getId() === $this->getId()) $color = TextFormat::GREEN;
		return ($combat ? "" : TextFormat::BOLD) . $color . $gang->getName() . ($combat ? "" : " (" . ($mm = $gang->getMemberManager())->getRoleName($mm->getMember($this)->getRole()) . ")") . PHP_EOL;
	}

	public function getLastBattleParticipant() : ?BattleParticipant{
		return $this->lastBattleParticipant;
	}
	
	public function setLastBattleParticipant(BattleParticipant $participant) : void{
		$this->lastBattleParticipant = $participant;
	}
	
	public function inLobby() : bool{
		return $this->getPosition()->getWorld() === Prison::getSpawn()->getWorld();
	}

}