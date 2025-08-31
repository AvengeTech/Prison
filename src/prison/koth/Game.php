<?php namespace prison\koth;

use pocketmine\Server;
use pocketmine\player\{
	Player,
	GameMode
};
use pocketmine\world\{
	World,
	Position,
    WorldCreationOptions
};
use pocketmine\entity\{
	Location
};
use pocketmine\math\Vector3;
use pocketmine\block\{
	BlockFactory,
	BlockTypeIds
};

use prison\Prison;
use prison\PrisonPlayer;
use prison\koth\entity\CenterCrystal;
use prison\koth\event\{
	KothWinEvent
};

use core\Core;
use core\network\protocol\{
	PlayerLoadActionPacket,
	ServerSubUpdatePacket
};
use core\scoreboards\ScoreboardObject;
use core\utils\BlockRegistry;
use core\utils\conversion\LegacyBlockIds;
use core\utils\ItemRegistry;
use core\utils\TextFormat;
use pocketmine\item\VanillaItems;

class Game{

	public CenterCrystal $crystal;
	public array $glass = [];
	public int $lastcolor = 0;

	public array $lines = [];
	public array $scoreboards = [];
	
	public ClaimQueue $queue;

	private ?PrisonPlayer $winner = null;
	private array $winnerRewardTexts = [];

	public bool $active = false;

	public function __construct(
		public string $id,
		public string $name,

		public string $level,
		int $time,

		public array $corners,
		public array $spawnpoints,
		public Vector3 $center,
		public int $distance = 5,

		array $glass = []
	){
		$lvl = $this->getLevel();
		$lvl?->setTime($time);
		$lvl?->stopTime();

		foreach($glass as $gd){
			$this->glass[] = new Vector3($gd[0], $gd[1], $gd[2]);
		}

		$pos = $this->getCenter()->add(0.5, 0, 0.5);
		$this->crystal = new CenterCrystal(new Location($pos->x, $pos->y, $pos->z, $this->getLevel(), 0, 0), $this);

		$this->queue = new ClaimQueue($this);

		$this->lines = [
			1 => TextFormat::EMOJI_CONTROLLER . TextFormat::AQUA . " KOTH Event " . TextFormat::EMOJI_CONTROLLER,
			2 => TextFormat::GRAY . "Map: " . TextFormat::GREEN . $this->getName(),
			3 => " ",
			4 => TextFormat::GRAY . "Uptime: ",
			5 => "  ",
			6 => TextFormat::EMOJI_MONEY_BAG . TextFormat::DARK_RED . " Your stats:",
			7 => TextFormat::GRAY . " - " . TextFormat::EMOJI_X . TextFormat::RED . " Kills: ",
			8 => TextFormat::GRAY . " - " . TextFormat::EMOJI_SKULL . TextFormat::YELLOW . " Deaths: ",
			9 => TextFormat::GRAY . " - " . TextFormat::EMOJI_STAR . TextFormat::GREEN . " Wins: ",
			10 => "   ",
			11 => TextFormat::EMOJI_LIGHTNING . TextFormat::AQUA . " Claiming: " . TextFormat::RED . "No one",
		];
	}

	public function tick() : void{
		$this->getClaimQueue()->tick();
	}

	public function reward(Player $player) : void{
		/** @var PrisonPlayer $player */
		$this->winner = $player;

		$player->addTechits(50000);
		$this->winnerRewardTexts[] = "50,000 techits";

		$player->getGameSession()->getMysteryBoxes()->addKeys("emerald", $cnt = (mt_rand(1, 3) !== 1 ? mt_rand(5, 15) : mt_rand(15, 30)));
		$this->winnerRewardTexts[] = $cnt . " emerald keys";

		if(mt_rand(0, 1) === 0){
			$count = mt_rand(1, 5);
			$player->getInventory()->addItem(VanillaItems::ENCHANTED_GOLDEN_APPLE()->setCount($count));
			$this->winnerRewardTexts[] = $count . " Enchanted Golden Apples";
		}
		if(mt_rand(1, 5) === 1){
			$player->getGameSession()->getEssence()->addEssence($eCount = (100 * (mt_rand(1, 3) !== 1 ? mt_rand(1, 5) : mt_rand(6, 10))));
			$this->winnerRewardTexts[] = number_format($eCount) . " Essence";
		}
		if(mt_rand(1, 20) === 1){
			$player->getGameSession()->getMysteryBoxes()->addKeys("divine", 1);
			$this->winnerRewardTexts[] = "1 divine key";
		}
		if(mt_rand(1, 10) === 1){
			if(mt_rand(1, 3) !== 1){
				$eos = ItemRegistry::ESSENCE_OF_SUCCESS()->setup(mt_rand(1, 5), -1, -1, false);
				$eos->init();
				$eos->setCount(mt_rand(1, 3));

				if($player->getInventory()->canAddItem($eos)){
					$player->getInventory()->addItem($eos);

					$this->winnerRewardTexts[] = TextFormat::WHITE . $eos->getCount() . "x" . $eos->getName();
				}
			}
			if(mt_rand(1, 3) !== 1){
				$eos = ItemRegistry::ESSENCE_OF_KNOWLEDGE()->setup(mt_rand(1, 5), -1, false);
				$eos->init();
				$eos->setCount(mt_rand(1, 3));

				if($player->getInventory()->canAddItem($eos)){
					$player->getInventory()->addItem($eos);

					$this->winnerRewardTexts[] = TextFormat::WHITE . $eos->getCount() . "x" . $eos->getName();
				}
			}
			if(mt_rand(1, 15) === 1){
				$eos = ItemRegistry::ESSENCE_OF_ASCENSION()->setup(mt_rand(1, 5), false);
				$eos->init();
				$eos->setCount(mt_rand(1, 3));

				if($player->getInventory()->canAddItem($eos)){
					$player->getInventory()->addItem($eos);

					$this->winnerRewardTexts[] = TextFormat::WHITE . $eos->getCount() . "x" . $eos->getName();
				}
			}
			if(mt_rand(1, 20) === 1){
				$eos = ItemRegistry::ESSENCE_OF_PROGRESS()->setup(mt_rand(1, 5), -1, false);
				$eos->init();
				$eos->setCount(mt_rand(1, 3));

				if($player->getInventory()->canAddItem($eos)){
					$player->getInventory()->addItem($eos);

					$this->winnerRewardTexts[] = TextFormat::WHITE . $eos->getCount() . "x" . $eos->getName();
				}
			}
		}

		$ev = new KothWinEvent($this, $player);
		$ev->call();

		$session = $player->getGameSession()->getKoth();
		$session->addWin();
		$session->setCooldown();

		$this->setGlassColor(0);

		$servers = [];
		foreach(Core::thisServer()->getSubServers(false, true) as $server){
			$servers[] = $server->getIdentifier();
		}
		(new ServerSubUpdatePacket([
			"server" => $servers,
			"type" => "koth",
			"data" => [
				"started" => false,
				"gameId" => $this->getId(),
				"message" => TextFormat::GI . TextFormat::YELLOW . $player->getName() . TextFormat::LIGHT_PURPLE . " won the " . TextFormat::AQUA . $this->getName() . TextFormat::LIGHT_PURPLE . " KOTH event! " . TextFormat::BOLD . TextFormat::GREEN . "GG"
			]
		]))->queue();

		$this->end();
	}

	public function getId() : string{
		return $this->id;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getLevel() : ?World{
		$world = Server::getInstance()->getWorldManager()->getWorldByName($this->getLevelName());

		if(is_null($world)){
			Server::getInstance()->getWorldManager()->generateWorld($this->getLevelName(), WorldCreationOptions::create());
			$level = Server::getInstance()->getWorldManager()->getWorldByName($this->getLevelName());
		}

		return $world;
	}

	public function getLevelName() : string{
		return $this->level;
	}

	public function getCorners() : array{
		return $this->corners;
	}

	public function getSpawnpoints() : array{
		return $this->spawnpoints;
	}

	public function getRandomSpawn() : Position{
		$spawn = $this->spawnpoints[mt_rand(0, count($this->spawnpoints) - 1)];
		return new Position($spawn->getX(), $spawn->getY(), $spawn->getZ(), $this->getLevel());
	}

	public function teleportTo(Player $player, bool $all = true, bool $tp = true) : void{
		/** @var PrisonPlayer $player */
		if(!($ts = Core::thisServer())->isSubServer() || $ts->getSubId() !== "pvp"){
			(new PlayerLoadActionPacket([
				"player" => $player->getName(),
				"server" => "prison-" . $ts->getTypeId() . "-pvp",
				"action" => "koth",
				"actionData" => ["gameId" => $this->getId()]
			]))->queue();
			$player->gotoPvPserver();
			return;
		}

		if($all){
			$session = ($gs = $player->getGameSession())->getMines();
			if($session->inMine()){
				$session->exitMine(false);
			}elseif($player->isBattleSpectator()){
				$player->stopSpectating();
			}

			if($player->getWorld() !== $this->getLevel()){
				$player->getGameSession()->getCombat()->setInvincible();
			}

			$player->getGameSession()->getKoth()->setGame($this);
			$this->addScoreboard($player);

			$player->setFlightMode(false);

			if($player->getGamemode() == GameMode::SURVIVAL()){
				$player->setGamemode(GameMode::ADVENTURE());
			}
			$player->sendMessage(TextFormat::GI . "Teleported to active KOTH game. (" . TextFormat::AQUA . $this->getName() . TextFormat::GRAY . ")");
			if($tp){
				$player->teleport($this->getRandomSpawn());
			}
		}elseif($tp){
			$player->teleport($this->getRandomSpawn());
		}
	}

	public function getCenter() : Vector3{
		return $this->center;
	}

	public function getDistance() : int{
		return $this->distance;
	}

	public function getGlassData() : array{
		return $this->glass;
	}

	public function getLastGlassColor() : int{
		return $this->lastcolor;
	}

	public function setGlassColor(int $color) : void{
		if($color == $this->getLastGlassColor()) return;
		$this->lastcolor = $color;
		foreach($this->getGlassData() as $co){
			$this->getLevel()->setBlock($co, BlockRegistry::getBlockById(LegacyBlockIds::legacyIdToTypeId(LegacyBlockIds::typeIdToLegacyId(BlockTypeIds::STAINED_GLASS), $color), -1), true);
		}
	}

	public function inCenter(Player $player) : bool{
		return $player->getWorld() === $this->getLevel() && $player->getPosition()->distance($this->getCenter()) <= $this->getDistance();
	}

	public function isInBorder(Player $player) : bool{
		$corners = $this->getCorners();
		$x = $player->getPosition()->getX();
		$z = $player->getPosition()->getZ();
		return $x >= $corners[0][0] && $x <= $corners[1][0] && $z >= $corners[0][1] && $z <= $corners[1][1];
	}

	public function nudge(Player $player) : void{
		$center = $this->getCenter();
		$dv = $center->subtract($player->getPosition()->x, $player->getPosition()->y, $player->getPosition()->z)->normalize();
		$player->knockback($dv->x, $dv->z, 0.2);
	}

	/** @return PrisonPlayer[] */
	public function getPlayers() : array{
		return $this->getLevel()->getPlayers();
	}

	public function getClaimQueue() : ClaimQueue{
		return $this->queue;
	}

	public function isActive() : bool{
		return $this->active;
	}

	public function setActive(bool $bool = true) : void{
		$this->active = $bool;
	}

	public function end(bool $sendPacket = false) : void{
		foreach($this->getPlayers() as $player){
			if(!$player->isLoaded()) continue;
			$player->getGameSession()?->getKoth()->setGame();
			$player->setAllowFlight(true);
			if(($cc = $player->getGameSession()->getCombat())->isTagged()){
				$cc->untag();
			}
			$textList = implode(PHP_EOL . TextFormat::GRAY . "- " . TextFormat::AQUA, $this->winnerRewardTexts);
			$player->gotoSpawn(
				false,
				(
					$this->winner !== $player ? "" :
					TextFormat::GI . "You won the KOTH match and earned the following rewards: " . PHP_EOL .
					TextFormat::GRAY . "- " . TextFormat::AQUA . $textList
				)
			);
		}
		$this->getClaimQueue()->reset();
		$this->setActive(false);
				
		if($sendPacket){
			$servers = [];
			foreach(Core::thisServer()->getSubServers(false, true) as $server){
				$servers[] = $server->getIdentifier();
			}
			(new ServerSubUpdatePacket([
				"server" => $servers,
				"type" => "koth",
				"data" => [
					"started" => false,
					"gameId" => $this->getId(),
					"message" => TextFormat::GI . TextFormat::LIGHT_PURPLE . "KOTH match " . TextFormat::YELLOW . $this->getName() . TextFormat::LIGHT_PURPLE . " has been force ended."
				]
			]))->queue();
		}
	}

	/* Scoreboard */
	public function updateScoreboardLines() : void{
		$network = Core::getInstance()->getNetwork();
		$seconds = $network->getUptime();
		$hours = floor($seconds / 3600);
		$minutes = floor(((int) ($seconds / 60)) % 60);
		$seconds = $seconds % 60;
		if(strlen((string) $hours) == 1) $hours = "0" . $hours;
		if(strlen((string) $minutes) == 1) $minutes = "0" . $minutes;
		if(strlen((string) $seconds) == 1) $seconds = "0" . $seconds;
		$left = $network->getRestartTime() - time();
		$this->lines[4] = TextFormat::GRAY . "Uptime: " . TextFormat::RED . $hours . TextFormat::GRAY . ":" . TextFormat::RED . $minutes . TextFormat::GRAY . ":" . TextFormat::RED . $seconds . " " . ($seconds %3 == 0 ? TextFormat::EMOJI_HOURGLASS_EMPTY : TextFormat::EMOJI_HOURGLASS_FULL) . " " . ($left <= 60 ? ($seconds %2 == 0 ? TextFormat::EMOJI_CAUTION : "") : "");

		$this->lines[11] = TextFormat::EMOJI_LIGHTNING . TextFormat::AQUA . " Claiming: " . (($fp = $this->getClaimQueue()->getFirstPlayer()) !== null ? TextFormat::GREEN . $fp->getName() : TextFormat::RED . "No one");
		if($fp !== null){
			$this->lines[12] = TextFormat::DARK_AQUA . "   Time: " . TextFormat::YELLOW . gmdate("i:s", time() - ($fp->time - $fp::TIME_NEEDED)) . TextFormat::GRAY . "/" . TextFormat::GREEN . "05:00";
		}else{
			$this->lines[12] = "";
		}

		ksort($this->lines);
		$this->updateAllScoreboards();
	}

	public function getLines() : array{
		return $this->lines;
	}

	public function getLinesFor(Player $player) : array{
		/** @var PrisonPlayer $player */
		$lines = $this->getLines();

		$session = $player->getGameSession()?->getKoth();
		if($session === null) return $lines;
		$lines[7] = TextFormat::GRAY . " - " . TextFormat::EMOJI_X . TextFormat::RED . " Kills: " . TextFormat::WHITE . number_format($session->getKills());// | " . $session->getWeeklyKills() . "[W] | " . $session->getMonthlyKills() . "[M]";
		$lines[8] = TextFormat::GRAY . " - " . TextFormat::EMOJI_SKULL . TextFormat::YELLOW . " Deaths: " . TextFormat::WHITE . number_format($session->getDeaths());// | " . $session->getWeeklyDeaths() . "[W] | " . $session->getMonthlyDeaths() . "[M]";
		$lines[9] = TextFormat::GRAY . " - " . TextFormat::EMOJI_STAR . TextFormat::GREEN . " Wins: " . TextFormat::WHITE . number_format($session->getWins());// | " . $session->getWeeklyWins() . "[W] | " . $session->getMonthlyWins() . "[M]";

		ksort($lines);
		return $lines;
	}

	public function getScoreboards() : array{
		return $this->scoreboards;
	}

	public function getScoreboard(Player $player) : ?ScoreboardObject{
		return $this->scoreboards[$player->getXuid()] ?? null;
	}

	public function addScoreboard(Player $player, bool $removeOld = true) : void{
		if($removeOld){
			Core::getInstance()->getScoreboards()->removeScoreboard($player, true);
		}

		$scoreboard = $this->scoreboards[$player->getXuid()] = new ScoreboardObject($player);
		$scoreboard->send($this->getLines());
	}

	public function removeScoreboard(Player $player, bool $addOld = true) : void{
		$scoreboard = $this->getScoreboard($player);
		if($scoreboard !== null){
			unset($this->scoreboards[$player->getXuid()]);
			$scoreboard->remove();
		}
		if($addOld){
			Core::getInstance()->getScoreboards()->addScoreboard($player);
		}
	}

	public function removeAllScoreboards() : void{
		foreach($this->scoreboards as $xuid => $sb){
			if(($pl = $sb->getPlayer()) instanceof Player){
				$sb->remove();
				Core::getInstance()->getScoreboards()->addScoreboard($pl);
			}
			unset($this->scoreboards[$xuid]);
		}
	}

	public function updateAllScoreboards() : void{
		foreach($this->scoreboards as $xuid => $sb){
			if($sb->getPlayer() instanceof Player) $sb->update($this->getLinesFor($sb->getPlayer()));
		}
	}

}