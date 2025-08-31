<?php

namespace prison\gangs\battle;

use pocketmine\{
	player\Player,
	Server
};
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\battle\arena\Arena;
use prison\gangs\battle\task\BattleRespawnTask;
use prison\gangs\objects\{
	Gang,
	TrophyData,
	AllianceManager
};

use core\Core;
use core\scoreboards\ScoreboardObject;
use core\utils\TextFormat;
use core\discord\objects\{
	Post,
	Embed,
	Field,
	Footer,
	Webhook
};

class Battle {

	const SYNC_CREATE = 0;
	const SYNC_DATA = 1;
	const SYNC_DELETE = 2;

	const MODE_NO_RESPAWN = 0;
	const MODE_LIMITED_RESPAWN = 1;
	const MODE_RESPAWN = 2;

	const DEFAULT_RESPAWNS = 3;

	const GAME_WAITING = 0;
	const GAME_COUNTDOWN = 1;
	const GAME_GET_READY = 2;
	const GAME_START = 3;
	const GAME_VICTORY = 4;

	const GAME_LENGTH = 300;

	public $id;

	public $gang1;
	public $gang1ready = false;
	public $gang1kills = 0;

	public $gang2;
	public $gang2ready = false;
	public $gang2kills = 0;

	public $kit;

	public $mode = self::MODE_NO_RESPAWN;
	public $respawns = 0;
	public $maxParticipants;

	public $arena;

	public $status = self::GAME_WAITING;
	public $timer = 0;

	public $scoreboards = [];
	public $lines = [];

	public $participants = [];
	public $eliminated = [];
	public $spectating = [];

	public $winner = null;
	public $draw = false;

	public $cancelled = false;
	public $ended = false;

	public function __construct(int $id, Gang $gang1, Gang $gang2, BattleKit $kit, int $mode = self::MODE_NO_RESPAWN, int $maxParticipants = 7, ?Arena $arena = null) {
		$this->id = $id;

		$this->gang1 = $gang1;
		$this->gang2 = $gang2;

		$this->kit = $kit;
		$this->mode = $mode;
		if ($mode == self::MODE_LIMITED_RESPAWN) {
			//todo: custom respawn limit in future?
			$this->respawns = self::DEFAULT_RESPAWNS;
		} elseif ($mode == self::MODE_RESPAWN) {
			$this->respawns = 10000;
		}
		$this->maxParticipants = $maxParticipants;

		$this->lines = [
			1 => TextFormat::GRAY . "May the best gang win!",
			2 => " ",
			3 => TextFormat::GRAY . "Kit: " . TextFormat::YELLOW . $this->getKit()->getName(),
			4 => TextFormat::GRAY . "Mode: " . TextFormat::GREEN . $this->getModeName(true),
			5 => TextFormat::GRAY . "Gangs:",
			//gangs
			8 => "  ",
			//status
		];
		$this->updateScoreboardLines(true, true, true);

		if (!$this->setupArena($arena)) {
			$this->cancel("No free arenas available!");
		} else {
			$this->addParticipant($gang1->getLeader()->getPlayer(), $gang1);
			$this->addParticipant($gang2->getLeader()->getPlayer(), $gang2);
		}
	}

	public function getBattleManager(): BattleManager {
		return Prison::getInstance()->getGangs()->getGangManager()->getBattleManager();
	}

	public function getAllianceManager(): AllianceManager {
		return Prison::getInstance()->getGangs()->getGangManager()->getAllianceManager();
	}

	public function getId(): int {
		return $this->id;
	}

	public function updateScoreboardLines(bool $timer = true, bool $status = false, bool $kills = false): void {
		if ($kills) {
			switch ($this->getStatus()) {
				case self::GAME_WAITING:
				case self::GAME_COUNTDOWN:
					$this->lines[5] = TextFormat::GRAY . "Gangs:";
					$this->lines[6] = TextFormat::GRAY . "- " . TextFormat::RED . ($g1 = $this->getGang1())->getName() . " " . ($this->isReady($g1) ? TextFormat::GREEN . "(READY!)" : "");
					$this->lines[7] = TextFormat::GRAY . "- " . TextFormat::BLUE . ($g2 = $this->getGang2())->getName() . " " . ($this->isReady($g2) ? TextFormat::GREEN . "(READY!)" : "");
					break;
				default:
					$this->lines[5] = TextFormat::GRAY . "Kills:";
					$this->lines[6] = TextFormat::GRAY . "- " . TextFormat::RED . ($g1 = $this->getGang1())->getName() . ": " . TextFormat::YELLOW . $this->getKills($g1);
					$this->lines[7] = TextFormat::GRAY . "- " . TextFormat::BLUE . ($g2 = $this->getGang2())->getName() . ": " . TextFormat::YELLOW . $this->getKills($g2);
					break;
			}
		}
		if ($status) {
			$this->lines[9] = TextFormat::GRAY . "Status: " . TextFormat::DARK_GREEN . $this->getStatusName();
		}
		if ($timer) {
			switch ($this->getStatus()) {
				case self::GAME_WAITING:
					$time = "X";
					break;
				case self::GAME_COUNTDOWN:
				case self::GAME_GET_READY:
					$time = $this->timer . " seconds";
					break;
				case self::GAME_START:
					$time = gmdate("i:s", $this->timer - time());
					break;
			}
			$this->lines[10] = TextFormat::GRAY . "Time: " . TextFormat::RED . $time;
		}
		ksort($this->lines);

		$this->updateAllScoreboards();
	}

	public function getLines(): array {
		return $this->lines;
	}

	public function getScoreboards(): array {
		return $this->scoreboards;
	}

	public function getScoreboard(Player $player): ?ScoreboardObject {
		return $this->scoreboards[$player->getXuid()] ?? null;
	}

	public function addScoreboard(Player $player, bool $removeOld = true): void {
		if ($removeOld) {
			Core::getInstance()->getScoreboards()->removeScoreboard($player, true);
		}

		$scoreboard = $this->scoreboards[$player->getXuid()] = new ScoreboardObject($player);
		$scoreboard->send($this->getLines());
	}

	public function removeScoreboard(Player $player, bool $addOld = true): void {
		$scoreboard = $this->getScoreboard($player);
		if ($scoreboard !== null) {
			unset($this->scoreboards[$player->getXuid()]);
			$scoreboard->remove();
		}
		if ($addOld) {
			Core::getInstance()->getScoreboards()->addScoreboard($player);
		}
	}

	public function removeAllScoreboards(): void {
		foreach ($this->scoreboards as $xuid => $sb) {
			if (($pl = $sb->getPlayer()) instanceof Player) {
				$sb->remove();
				Core::getInstance()->getScoreboards()->addScoreboard($pl);
			}
			unset($this->scoreboards[$xuid]);
		}
	}

	public function updateAllScoreboards(): void {
		foreach ($this->scoreboards as $xuid => $sb) {
			if ($sb->getPlayer() instanceof Player) $sb->update($this->getLines());
		}
	}

	public function tick(): void {
		switch ($this->getStatus()) {
			case self::GAME_WAITING:
				$pc = count($this->getParticipants());
				foreach ($this->getParticipants() as $key => $participant) {
					$player = $participant->getPlayer();
					if (!$player instanceof Player) {
						unset($this->participants[$key]);
						$this->setReady($participant->getGang(), false);
						$leader = $participant->getGang()->getLeader();
						if (!$leader->isOnline()) {
							$this->cancel("One of the gang leaders has left the server!");
							break;
						}
					}
				}
				if (
					count($this->getParticipantsFrom($this->getGang1())) == count($this->getParticipantsFrom($this->getGang2())) &&
					$this->isReady($this->getGang1()) && $this->isReady($this->getGang2())
				) {
					$this->setStatus(self::GAME_COUNTDOWN);
					$this->setTimer(10);
				}
				break;

			case self::GAME_COUNTDOWN:
				$this->timer--;

				$pc = count($this->getParticipants());
				foreach ($this->getParticipants() as $key => $participant) {
					$player = $participant->getPlayer();
					if (!$player instanceof Player) {
						unset($this->participants[$key]);
						$this->setReady($participant->getGang(), false);
						$this->setStatus(self::GAME_WAITING);
						$this->setTimer(0);
						$leader = $participant->getGang()->getLeader();
						if (!$leader->isOnline()) {
							$this->cancel("One of the gang leaders has left the server!");
							break;
						}
					}
				}
				if (
					count($this->getParticipantsFrom($this->getGang1())) == count($this->getParticipantsFrom($this->getGang2())) &&
					$this->isReady($this->getGang1()) && $this->isReady($this->getGang2())
				) {
					if ($this->timer <= 0) {
						Server::getInstance()->broadcastMessage(TextFormat::PI . "A gang battle is starting between " . TextFormat::RED . $this->getGang1()->getName() . TextFormat::GRAY . " and " . TextFormat::BLUE . $this->getGang2()->getName() . TextFormat::GRAY . "! Type " . TextFormat::YELLOW . "/gang spectate" . TextFormat::GRAY . " to watch this battle LIVE!");

						$total = count($this->getParticipantsFrom($this->getGang1()));
						$g1k = 1;
						$g2k = 1;
						$gang = -1;
						$arena = $this->getArena();
						$half = $arena->getHalf(1);
						foreach ($this->getParticipantsFrom($this->getGang1()) as $p) {
							$pos = $half->getSpawnpoint($total, $g1k);
							$g1k++;
							$player = $p->getPlayer();
							$session = $player->getGameSession()->getMines();
							if ($session->inMine()) $session->exitMine(false);
							if (($ks = $player->getGameSession()->getKoth())->inGame()) $ks->setGame();
							$player->teleport($pos);
						}
						$half = $arena->getHalf(2);
						foreach ($this->getParticipantsFrom($this->getGang2()) as $p) {
							$pos = $half->getSpawnpoint($total, $g2k);
							$g2k++;
							$player = $p->getPlayer();
							$session = $player->getGameSession()->getMines();
							if ($session->inMine()) $session->exitMine(false);
							if (($ks = $player->getGameSession()->getKoth())->inGame()) $ks->setGame();
							$player->teleport($pos);
						}
						foreach ($this->getParticipants() as $p) {
							$p->saveInventories();
							$this->getKit()->equip($p->getPlayer());
							($pl = $p->getPlayer())->sendMessage(TextFormat::GI . "Gang battle is starting soon... Prepare your weapons!");
							$pl->setLastBattleParticipant($p);
							$pl->setNoClientPredictions(true);
						}

						Core::getInstance()->getEntities()->getFloatingText()->getText("battle-spectate")->update();
						$this->setStatus(self::GAME_GET_READY);
						$this->setTimer(15);
					}
				} else {
					$this->setStatus(self::GAME_WAITING);
				}
				break;

			case self::GAME_GET_READY:
				$this->timer--;

				foreach ($this->getParticipants() as $key => $participant) {
					$player = $participant->getPlayer();
					if (!$player instanceof Player) {
						unset($this->participants[$key]);
						$this->setReady($participant->getGang(), false);
						$this->setStatus(self::GAME_WAITING);
						$this->setTimer(0);
						$this->restoreAllInventories();
						$this->allGotoSpawn();
						$leader = $participant->getGang()->getLeader();
						if (!$leader->isOnline()) {
							$this->cancel("One of the gang leaders has left the server!");
							break;
						}
					}
				}
				if ($this->timer <= 0) {
					foreach ($this->getParticipants() as $p) {
						($pl = $p->getPlayer())->sendMessage(TextFormat::GI . "Battle has started! May the best gang win!");
						$pl->setNoClientPredictions(false);
						$pl->setAllowFlight(false);
					}
					$this->setStatus(self::GAME_START);
					$this->setTimer(time() + self::GAME_LENGTH);
				}

				break;

			case self::GAME_START:
				foreach ($this->getParticipants() as $key => $participant) {
					$player = $participant->getPlayer();
					if (!$player instanceof Player) {
						$this->eliminated[$participant->getXuid()] = $participant;
						unset($this->participants[$key]);
					}
					if (
						empty($this->getParticipantsFrom($this->getGang1())) ||
						empty($this->getParticipantsFrom($this->getGang2())) ||
						$this->timer <= time()
					) {
						$this->end();
					}
				}
				break;

			case self::GAME_VICTORY:

				break;
		}

		$this->updateScoreboardLines();

		foreach ($this->getSpectating() as $key => $spec) {
			$pl = $spec->getPlayer();
			if ($pl instanceof Player && $this->getParticipantBy($pl) === null) {
				$pl->sendTip(TextFormat::YELLOW . "Spectating Battle!");
			} else {
				unset($this->spectating[$key]);
			}
		}
	}

	public function getGang1(): Gang {
		return $this->getBattleManager()->getGangManager()->getGangByGang($this->gang1);
	}

	public function getGang2(): Gang {
		return $this->getBattleManager()->getGangManager()->getGangByGang($this->gang2);
	}

	public function getOppositeGang(Gang $gang): Gang {
		if ($gang->getId() == $this->getGang1()->getId()) return $this->getGang2();
		return $this->getGang1();
	}

	public function isGangInBattle(Gang $gang): bool {
		return $gang->getId() == $this->getGang1()->getId() || $gang->getId() == $this->getGang2()->getId();
	}

	public function isReady(Gang $gang): bool {
		if ($gang->getId() == $this->getGang1()->getId()) {
			return $this->gang1ready;
		} elseif ($gang->getId() == $this->getGang2()->getId()) {
			return $this->gang2ready;
		}
		return false;
	}

	public function setReady(Gang $gang, bool $ready = true): void {
		if ($gang->getId() == $this->getGang1()->getId()) {
			$this->gang1ready = $ready;
		} elseif ($gang->getId() == $this->getGang2()->getId()) {
			$this->gang2ready = $ready;
		}

		$this->updateScoreboardLines(false, false, true);
	}

	public function getKills(Gang $gang): int {
		if ($gang->getId() == $this->getGang1()->getId())
			return $this->gang1kills;
		if ($gang->getId() == $this->getGang2()->getId())
			return $this->gang2kills;
	}

	public function addKill(Gang $gang): void {
		if ($gang->getId() == $this->getGang1()->getId())
			$this->gang1kills++;
		if ($gang->getId() == $this->getGang2()->getId())
			$this->gang2kills++;

		$this->updateScoreboardLines(false, false, true);
	}

	public function getMode(): int {
		return $this->mode;
	}

	public function getModeName(bool $sb = false): string {
		switch ($this->getMode()) {
			default:
			case self::MODE_NO_RESPAWN:
				return "No Respawns";
			case self::MODE_LIMITED_RESPAWN:
				return ($sb ? "Lim. Respawn" : "Limited Respawn") . " (" . $this->getRespawns() . ")";
			case self::MODE_RESPAWN:
				return "Respawns";
		}
	}

	public function getRespawns(): int {
		return $this->respawns;
	}

	public function getMaxParticipants(): int {
		return $this->maxParticipants;
	}

	public function getKit(): BattleKit {
		return $this->kit;
	}

	public function getArena(): ?Arena {
		return $this->arena;
	}

	public function setupArena(?Arena $arena = null): bool {
		$arena = Prison::getInstance()->getGangs()->getGangManager()->getBattleManager()->getFreeArena();
		if ($arena !== null) {
			$arena->getHalf(1)->setGang($this->getGang1());
			$arena->getHalf(2)->setGang($this->getGang2());
			$this->arena = $arena;
			return true;
		}
		return false; //no free arenas
	}

	public function getStatus(): int {
		return $this->status;
	}

	public function getStatusName(): string {
		switch ($this->getStatus()) {
			default:
			case self::GAME_WAITING:
				return "Waiting...";
			case self::GAME_COUNTDOWN:
				return "Countdown...";
			case self::GAME_GET_READY:
				return "Get ready...";
			case self::GAME_START:
				return "Start!";
			case self::GAME_VICTORY:
				return "Victory!";
		}
	}

	public function setStatus(int $status): void {
		$this->status = $status;

		$this->updateScoreboardLines(false, true, true);
	}

	public function getTimer(): int {
		return $this->timer;
	}

	public function setTimer(int $time): void {
		$this->timer = $time;
	}

	public function getParticipants(): array {
		return $this->participants;
	}

	public function addParticipant(Player $player, Gang $gang): bool {
		if ($this->getParticipantBy($player) !== null) return false;
		$this->participants[$player->getXuid()] = new BattleParticipant($player, $gang, $this);

		$this->addScoreboard($player);

		return true;
	}

	public function removeParticipant(Player $player, bool $spawn = false): bool {
		/** @var PrisonPlayer $player */
		if (($p = $this->getParticipantBy($player)) === null) return false;
		unset($this->participants[$player->getXuid()]);
		if ($this->getStatus() > 2) {
			$p->restoreInventory();
			if ($spawn) {
				$player->gotoSpawn(true);
				$this->removeScoreboard($player);
			}
			$this->eliminated[$player->getXuid()] = $p;
			foreach ($this->getParticipants() as $pp) {
				if (($pl = $pp->getPlayer()) instanceof Player) $pl->sendMessage(TextFormat::RI . TextFormat::YELLOW . $player->getName() . TextFormat::GRAY . " has been eliminated!");
			}
			if (
				empty($this->getParticipantsFrom($this->getGang1())) ||
				empty($this->getParticipantsFrom($this->getGang2()))
			) {
				$this->end();
			}
		} else {
			$gang = $p->getGang();
			if ($this->getStatus() == self::GAME_GET_READY) {
				$p->restoreInventory();
				$this->restoreAllInventories();
				$this->allGotoSpawn();
			}
			$this->setReady($gang, false);
			$this->setStatus(self::GAME_WAITING);
			if ($gang->isLeader($player)) {
				$this->cancel("A gang leader has left.");
			} elseif (
				empty($this->getParticipantsFrom($this->getGang1())) ||
				empty($this->getParticipantsFrom($this->getGang2()))
			) {
				$this->cancel("One or more gangs have no more participants left!");
			} else {
				$gang->getLeader()->getPlayer()->sendMessage(TextFormat::RI . TextFormat::YELLOW . $player->getName() . TextFormat::GRAY . " has left the battle before it started!");
			}
		}
		Core::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player): void { //restores inv if it doesn't for some reason above
			if ($player->isConnected()) {
				$hasBattleItemsStill = false;
				foreach ($player->getInventory()->getContents() as $item) {
					if ($item->getNamedTag()->getInt(BattleKit::BATTLE_TAG, 0) == 0) $hasBattleItemsStill = true;
				}
				$bp = $player->getLastBattleParticipant();
				if ($bp instanceof BattleParticipant && $hasBattleItemsStill)
					$bp->restoreInventory();
			}
		}), 20);
		return false;
	}

	public function getParticipantBy(Player $player): ?BattleParticipant {
		return $this->participants[$player->getXuid()] ?? null;
	}

	public function getParticipantsFrom(Gang $gang): array {
		$participants = [];
		foreach ($this->getParticipants() as $p) {
			if ($p->getGang()->getId() == $gang->getId()) $participants[] = $p;
		}
		return $participants;
	}

	public function isParticipating(Player $player): bool {
		return $this->getParticipantBy($player) !== null;
	}

	public function restoreAllInventories(): void {
		foreach ($this->getParticipants() as $pp) {
			if ($pp->getPlayer() instanceof Player) $pp->restoreInventory();
		}
	}

	public function allGotoSpawn(): void {
		foreach ($this->getParticipants() as $pp) {
			if (($pl = $pp->getPlayer()) instanceof Player) $pl->gotoSpawn(true);
			$pl->setNoClientPredictions(false);
		}
		foreach ($this->getSpectating() as $sp) {
			if (($s = $sp->getPlayer()) instanceof Player) $s->gotoSpawn(true);
		}
	}

	public function areParticipantsEven(): bool {
		return count($this->getParticipantsFrom($this->getGang1())) == count($this->getParticipantsFrom($this->getGang2()));
	}

	public function getEliminated(): array {
		return $this->eliminated;
	}

	public function getEliminatedFrom(Gang $gang): array {
		$eliminated = [];
		foreach ($this->getEliminated() as $p) {
			if ($p->getGang()->getId() == $gang->getId()) $eliminated[] = $p;
		}
		return $eliminated;
	}

	public function getSpectating(): array {
		return $this->spectating;
	}

	public function addSpectator(Player $player, bool $teleport = true): void {
		/** @var PrisonPlayer $player */
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		$this->spectating[$player->getXuid()] = new Spectator($player, $gang, $this);
		$player->despawnFromAll();

		if ($teleport) {
			$center = $this->getArena()->getCenter()->getDot();
			$center = Position::fromObject($center, ($level = $this->getArena()->getLevel()));
			$player->teleport($center);
		}

		$this->addScoreboard($player);

		$player->setFlightMode();
	}

	public function removeSpectator(Player $player, bool $removeSb = true): void {
		/** @var PrisonPlayer $player */
		unset($this->spectating[$player->getXuid()]);
		$player->spawnToAll();

		if ($removeSb) $this->removeScoreboard($player);
		$player->setFlightMode(false);
	}

	public function getSpectator(Player $player): ?Spectator {
		return $this->spectating[$player->getXuid()] ?? null;
	}

	public function isSpectator(Player $player): bool {
		return $this->getSpectator($player) !== null;
	}

	public function commenceRespawn(Player $player): void {
		/** @var PrisonPlayer $player */
		$this->addSpectator($player);

		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getCursorInventory()->clearAll();

		$player->stopBleeding();

		$player->getEffects()->clear();

		$player->setHealth(20);
		$player->getHungerManager()->setFood(20);
		$player->getHungerManager()->setSaturation(20);

		Prison::getInstance()->getEnchantments()->calculateCache($player);

		$player->sendTitle(TextFormat::RED . "Respawning in...", TextFormat::YELLOW . BattleRespawnTask::RESPAWN_COUNTDOWN, 5, 20, 5);

		Prison::getInstance()->getScheduler()->scheduleDelayedTask(new BattleRespawnTask($player, $this), 20);
	}

	public function respawn(Player $player): void {
		$this->removeSpectator($player, false);
		$this->getKit()->equip($player);

		$center = $this->getArena()->getCenter()->getDot();
		$center = Position::fromObject($center, ($level = $this->getArena()->getLevel()));
		$player->teleport($center);
	}

	public function end(): void {
		if ($this->ended()) return;
		$this->ended = true;

		$this->restoreAllInventories();
		$this->allGotoSpawn();

		$g1 = $this->getGang1();
		$g2 = $this->getGang2();
		$bm = $this->getBattleManager();
		$br = $bm->hasBattledRecently($g1, $g2);
		$allied = $this->getAllianceManager()->areAllies($g1, $g2);

		$g1kills = 0;
		$g1deaths = 0;
		foreach (
			array_merge(
				$this->getParticipantsFrom($g1),
				$this->getEliminatedFrom($g1)
			) as $pp
		) {
			$g1kills += $pp->getKills();
			$g1deaths += $pp->getDeaths();
		}
		$g1bs = $g1->getBattleStatManager();

		$g2kills = 0;
		$g2deaths = 0;
		foreach (
			array_merge(
				$this->getParticipantsFrom($g2),
				$this->getEliminatedFrom($g2)
			) as $pp
		) {
			$g2kills += $pp->getKills();
			$g2deaths += $pp->getDeaths();
		}
		$g2bs = $g2->getBattleStatManager();

		switch ($this->getMode()) {
			case self::MODE_NO_RESPAWN:
			case self::MODE_LIMITED_RESPAWN:
				if (
					empty($this->getParticipantsFrom($g1)) &&
					empty($this->getParticipantsFrom($g2))
				) { //should be impossible?
					$this->draw = true;
				} elseif (empty($this->getParticipantsFrom($g1))) {
					$this->setWinner($g2);
					if (!$br && !$allied) {
						$g2->addTrophies(min(TrophyData::MAX_BATTLE_KILL, TrophyData::EVENT_BATTLE_KILL * $g2kills));
					}
				} elseif (empty($this->getParticipantsFrom($g2))) {
					$this->setWinner($g1);
					if (!$br && !$allied) {
						$g1->addTrophies(min(TrophyData::MAX_BATTLE_KILL, TrophyData::EVENT_BATTLE_KILL * $g1kills));
					}
				} else { //most standing wins
					$pf1 = $this->getParticipantsFrom($g1);
					$pf2 = $this->getParticipantsFrom($g2);
					switch (true) {
						case count($pf1) > count($pf2):
							$this->setWinner($g1);
							if (!$br && !$allied) {
								$g1->addTrophies(min(TrophyData::MAX_BATTLE_KILL, TrophyData::EVENT_BATTLE_KILL * $g1kills));
							}
							break;
						case count($pf2) > count($pf1):
							$this->setWinner($g2);
							if (!$br && !$allied) {
								$g2->addTrophies(min(TrophyData::MAX_BATTLE_KILL, TrophyData::EVENT_BATTLE_KILL * $g2kills));
							}
							break;
						case count($pf1) == count($pf2):
							$this->draw = true;
							break;
					}
				}
				break;
			case self::MODE_RESPAWN:
				switch (true) {
					case $g1kills > $g2kills:
						$this->setWinner($g1);
						if (!$br && !$allied) {
							$g1->addTrophies(min(TrophyData::MAX_BATTLE_KILL, TrophyData::EVENT_BATTLE_KILL * $g1kills));
						}
						break;
					case $g2kills > $g1kills:
						$this->setWinner($g2);
						if (!$br && !$allied) {
							$g2->addTrophies(min(TrophyData::MAX_BATTLE_KILL, TrophyData::EVENT_BATTLE_KILL * $g2kills));
						}
						break;
					case $g1kills == $g2kills:
						$this->draw = true;
						break;
				}
				break;
		}

		$stats = new BattleStats($this, $br, $allied);
		$g1bs->addRecentBattleStats($stats);
		$g2bs->addRecentBattleStats($stats);

		$this->reward($stats);
	}

	public function ended(): bool {
		return $this->ended;
	}

	public function getWinner(): ?Gang {
		return $this->winner;
	}

	public function getLoser(): ?Gang {
		if (($winner = $this->getWinner()) === null) return null;
		return $this->getOppositeGang($this->getWinner());
	}

	public function isWinner(Gang $gang): bool {
		return $this->getWinner() !== null && $this->getWinner()->getId() == $gang->getId();
	}

	public function setWinner(?Gang $gang = null): void {
		$this->winner = $gang;
	}

	public function isDraw(): bool {
		return $this->draw;
	}

	public function reward(BattleStats $stats): void {
		$gang1 = $this->getGang1();
		$gang2 = $this->getGang2();
		$bm = $this->getBattleManager();
		$br = $bm->hasBattledRecently($gang1, $gang2);
		$allied = $this->getAllianceManager()->areAllies($gang1, $gang2);

		if ($this->isDraw()) {
			foreach (array_merge($this->getParticipants(), $this->getEliminated()) as $pp) {
				if (($pl = $pp->getPlayer()) instanceof Player) {
					$pl->sendMessage(TextFormat::YI . "Your gang battle ended in a draw!");
				}
			}
			foreach ($this->getSpectating() as $sp) {
				if ($sp->getPlayer() instanceof Player) $sp->getPlayer()->sendMessage(TextFormat::YI . "The gang battle you are spectating ended in a draw!");
			}

			if (!$br) {
				$bm->addBattledRecently($gang1, $gang2);
				if (!$allied) {
					$gang1->getBattleStatManager()->addDraw();
					$gang2->getBattleStatManager()->addDraw();
				}
			}
		} else {
			$winner = $this->getWinner();
			$loser = $this->getLoser();

			Server::getInstance()->broadcastMessage(TextFormat::PI . TextFormat::YELLOW . $winner->getName() . TextFormat::GRAY . " has won a gang battle against " . TextFormat::YELLOW . $loser->getName() . "!");


			if (!$br) {
				$bm->addBattledRecently($gang1, $gang2);
				if (!$allied) {
					($wbs = $winner->getBattleStatManager())->addWin();
					($lbs = $loser->getBattleStatManager())->addLoss();

					$winner->addTrophies(TrophyData::EVENT_BATTLE_WIN);
					$loser->takeTrophies(TrophyData::EVENT_BATTLE_LOSE);
				}
			}

			$post = new Post("", "Gang Battle Log - " . Core::getInstance()->getNetwork()->getIdentifier(), "[REDACTED]", false, "", [
				new Embed("", "rich", "**" . $winner->getName() . "** just won a battle against **" . $loser->getName() . "**", "", "ffb106", new Footer("Wow! What an epic battle! | " . date("F j, Y, g:ia", time())), "", "[REDACTED]", null, [
					new Field($winner->getName() . " kills", $stats->getKills($winner), true),
					new Field($loser->getName() . " kills", $stats->getKills($loser), true),
					new Field("Mode", $this->getModeName(), true),
					new Field("Kit", $this->getKit()->getName(), true),
				])
			]);
			$post->setWebhook(Webhook::getWebhookByName("battle-log"));
			$post->send();
		}

		foreach (array_merge($this->getParticipants(), $this->getEliminated()) as $pp) {
			if (($pl = $pp->getPlayer()) instanceof Player) {
				$pl->sendMessage(TextFormat::YI . "You can type " . TextFormat::YELLOW . "/gang results" . TextFormat::GRAY . " to view stats of your most recent battles!");
			}
		}

		$this->cancel();
	}

	public function cancel(string $reason = ""): void {
		$this->cancelled = true;
		$this->removeAllScoreboards();
		if ($this->getStatus() > self::GAME_COUNTDOWN) $this->restoreAllInventories();
		$this->getBattleManager()->cancelBattle($this->getId(), $reason);
	}

	public function isCancelled(): bool {
		return $this->cancelled;
	}

	public function sync(int $type): void {
		$servers = [];
		foreach (Core::thisServer()->getSubServers(false, true) as $server) {
			$servers[] = $server->getIdentifier();
		}

		$data = [
			"server" => $servers,
			"type" => "gangBattleSync",
			"data" => [
				"gang1" => $this->getGang1()->getId(),
				"gang2" => $this->getGang2()->getId(),
				"type" => Gang::SYNC_BATTLE,
				"change" => $type,
				"battleId" => $this->getId(),
			]
		];
		switch ($type) {
			case self::SYNC_CREATE:

				break;
			case self::SYNC_DATA:

				break;
		}
		(new ServerSubUpdatePacket($data))->queue();
	}
}
