<?php

namespace prison\rankup;

use pocketmine\Server;
use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;

use core\Core;
use core\discord\objects\{
	Post,
	Webhook,
	Embed,
	Field,
	Footer
};
use core\session\component\{
	ComponentRequest,
	SaveableComponent
};
use core\session\mysqli\data\MySqlQuery;
use core\utils\TextFormat;

/** @method PrisonPlayer getPlayer() */
class RankUpComponent extends SaveableComponent {

	const BASE_PRESTIGE_PRICE = 1500000;

	public string $rank = "a";
	public int $prestige = 0;
	public int $playtime = 0;

	public int $playcache = 0;

	public function getName(): string {
		return "rankup";
	}

	public function getRank(): string {
		return $this->rank;
	}

	public function setRank(string $rank): void {
		$this->rank = $rank;
		$this->setChanged();
	}

	public function canRankUp(?string $newrank = null): bool {
		$rank = $this->getRank();
		if ($rank == "free") return false;

		if (is_null($newrank)) {
			$newrank = $rank;
			$newrank++;
		}

		if ($rank == "z") $newrank = "free";

		return Prison::getInstance()->getRankUp()->getRankUpPrice($newrank) <= $this->getPlayer()->getTechits();
	}

	public function rankup(bool $charge = true): void {
		$player = $this->getPlayer();

		$rank = $this->getRank();
		$newrank = ($rank == "z" ? "free" : ++$rank);
		$price = Prison::getInstance()->getRankUp()->getRankUpPrice($newrank);

		$before = $this->getRankTag();
		$this->setRank($newrank);
		$after = $this->getRankTag();
		if ($charge) $player->takeTechits($price);

		$post = new Post("", "Rank up Log - " . Core::getInstance()->getNetwork()->getIdentifier(), "[REDACTED]", false, "", [
			new Embed("", "rich", "**" . $this->getGamertag() . "** just ranked up!", "", "ffb106", new Footer("cornelius | " . date("F j, Y, g:ia", time())), "", "[REDACTED]", null, [
				new Field("Before", $before, true),
				new Field("After", $after, true),
				new Field("Charged", $charge ? "YES" . ' (Techits: ' . number_format($price) . ')' : "NO", true),
				new Field("RankUp Type", 'Regular', true),
			])
		]);
		$post->setWebhook(Webhook::getWebhookByName("prison-rankup-log"));
		$post->send();

		$player->updateNametag();
		$player->updateChatFormat();

		if ($this->getPrestige() == 0) {
			foreach (Server::getInstance()->getOnlinePlayers() as $pl) {
				/** @var PrisonPlayer $pl */
				$pl->playSound("random.levelup", null, 75);
			}
		}
	}

	public function rankupMax(string $newrank, bool $charge = true): void {
		$player = $this->getPlayer();

		$originalRank = $this->getRank();
		$beforeRankTag = $this->getRankTag();

		$this->setRank($newrank);

		$afterRankTag = $this->getRankTag();

		if ($charge) {
			$price = Prison::getInstance()->getRankUp()->getRankUpPrice($newrank);
			$rank = Prison::getInstance()->getRankUp()->getNextRank($originalRank);

			while ($rank !== $newrank) {
				$price += Prison::getInstance()->getRankUp()->getRankUpPrice($rank);
				$rank = Prison::getInstance()->getRankUp()->getNextRank($rank);
			}

			$player->takeTechits($price);
		}

		$post = new Post("", "Rank up Log - " . Core::getInstance()->getNetwork()->getIdentifier(), "[REDACTED]", false, "", [
			new Embed("", "rich", "**" . $this->getGamertag() . "** just ranked up!", "", "ffb106", new Footer("cornelius | " . date("F j, Y, g:ia", time())), "", "[REDACTED]", null, [
				new Field("Before", $beforeRankTag, true),
				new Field("After", $afterRankTag, true),
				new Field("Charged", $charge ? "YES" . ' (Techits: ' . number_format($price) . ')' : "NO", true),
				new Field("RankUp Type", 'Max', true),
			])
		]);
		$post->setWebhook(Webhook::getWebhookByName("prison-rankup-log"));
		$post->send();

		$player->updateNametag();
		$player->updateChatFormat();

		if ($this->getPrestige() == 0) {
			/** @var PrisonPlayer $pl */
			foreach (Server::getInstance()->getOnlinePlayers() as $pl) {
				$pl->playSound("random.levelup", null, 75);
			}
		}
	}

	public function getPrestige(): int {
		return $this->prestige;
	}

	public function getPrestigePrice(): int {
		return self::BASE_PRESTIGE_PRICE * ($this->getPrestige() + 1);
	}

	public function setPrestige(int $value): void {
		$this->prestige = $value;
		$this->setChanged();
	}

	public function getRankTag(): string {
		return ($this->getPrestige() > 0 ? $this->getPrestige() . " " : "") . strtoupper($this->getRank());
	}

	public function prestige(): void {
		$player = $this->getPlayer();

		$this->setRank("a");
		$before = $this->getPrestige();
		$this->setPrestige($after = $this->getPrestige() + 1);
		$post = new Post("", "Rank up Log - " . Core::getInstance()->getNetwork()->getIdentifier(), "[REDACTED]", false, "", [
			new Embed("", "rich", "**" . $this->getGamertag() . "** just gained a prestige level!", "", "ffb106", new Footer("george | " . date("F j, Y, g:ia", time())), "", "[REDACTED]", null, [
				new Field("Before", $before, true),
				new Field("After", $after, true),
			])
		]);
		$post->setWebhook(Webhook::getWebhookByName("prison-rankup-log"));
		$post->send();

		$player->updateNametag();
		$player->updateChatFormat();

		$prestige = $this->getPrestige();
		if ($prestige == 1 || $prestige % 5 == 0) {
			if ($prestige % 100 == 0) {
				$sound = "mob.enderdragon.growl";
			} else {
				$sound = "mob.blaze.death";
			}
			/** @var PrisonPlayer $pl */
			foreach (Server::getInstance()->getOnlinePlayers() as $pl) {
				$pl->playSound($sound, null, 75);
				$pl->sendMessage(TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "(" . TextFormat::OBFUSCATED . TextFormat::GOLD . "!" . TextFormat::RESET . TextFormat::LIGHT_PURPLE . ") " . TextFormat::YELLOW . $player->getName() . TextFormat::GRAY . " has reached prestige " . TextFormat::BOLD . TextFormat::GREEN . $prestige);
			}
		}

		$session = $player->getGameSession()->getMines();
		if ($session->inMine()) {
			$session->exitMine();
		}

		$player->removeChildEntities();
	}

	public function getPlaytime(): int {
		return $this->playtime;
	}

	public function getFormattedPlaytime(bool $withcache = false): string {
		$seconds = $this->getPlaytime() + ($withcache ? $this->getAddedPlaytime() : 0);
		$dtF = new \DateTime("@0");
		$dtT = new \DateTime("@$seconds");
		return $dtF->diff($dtT)->format("%a days, %h hours, %i minutes");
	}

	public function setPlaytime(int $value): void {
		$this->playtime = $value;
		$this->setChanged();
	}

	public function addPlaytime(int $value = 1): void {
		$this->setPlaytime($this->getPlaytime() + $value);
	}

	public function getPlayCache(): int {
		return $this->playcache;
	}

	public function getAddedPlaytime(): int {
		return $this->getPlayCache() == 0 || !$this->getPlayer() instanceof Player ? 0 : time() - $this->getPlayCache();
	}

	public function createTables(): void {
		$db = $this->getSession()->getSessionManager()->getDatabase();
		foreach (
			[
				"CREATE TABLE IF NOT EXISTS rankup_player(xuid BIGINT(16) NOT NULL UNIQUE, `rank` VARCHAR(4) NOT NULL DEFAULT 'a', prestige INT(4) NOT NULL DEFAULT 0, playtime INT NOT NULL DEFAULT 0);",
			] as $query
		) $db->query($query);
	}

	public function loadAsync(): void {
		$this->playcache = time();

		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main", "SELECT `rank`, prestige, playtime FROM rankup_player WHERE xuid=?", [$this->getXuid()]));
		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null): void {
		$result = $request->getQuery()->getResult();
		$rows = (array) $result->getRows();
		if (count($rows) > 0) {
			$data = array_shift($rows);
			$this->rank = $data["rank"];
			$this->prestige = $data["prestige"];
			$this->playtime = $data["playtime"];
		}

		parent::finishLoadAsync($request);
	}

	public function verifyChange(): bool {
		$verify = $this->getChangeVerify();
		return $this->getRank() !== $verify["rank"] || $this->getPrestige() !== $verify["prestige"];
	}

	public function saveAsync(): void {
		if (!$this->isLoaded()) return;

		$this->setChangeVerify([
			"rank" => $this->getRank(),
			"prestige" => $this->getPrestige(),
		]);

		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery(
			"main",
			"INSERT INTO rankup_player(xuid, `rank`, prestige, playtime) VALUES(?, ?, ?, ?) ON DUPLICATE KEY UPDATE `rank`=VALUES(`rank`), prestige=VALUES(prestige), playtime=VALUES(playtime)",
			[$this->getXuid(), $this->getRank(), $this->getPrestige(), $this->getPlaytime() + $this->getAddedPlaytime()]
		));
		$this->newRequest($request, ComponentRequest::TYPE_SAVE);
		parent::saveAsync();
	}

	public function save(): bool {
		if (!$this->isLoaded()) return false;

		$xuid = $this->getXuid();
		$rank = $this->getRank();
		$prestige = $this->getPrestige();
		$playtime = $this->getPlaytime() + $this->getAddedPlaytime();

		$db = $this->getSession()->getSessionManager()->getDatabase();
		$stmt = $db->prepare("INSERT INTO rankup_player(xuid, `rank`, prestige, playtime) VALUES(?, ?, ?, ?) ON DUPLICATE KEY UPDATE `rank`=VALUES(`rank`), prestige=VALUES(prestige), playtime=VALUES(playtime)");
		$stmt->bind_param("isii", $xuid, $rank, $prestige, $playtime);
		$stmt->execute();
		$stmt->close();

		return parent::save();
	}

	public function getSerializedData(): array {
		return [
			"rank" => $this->getRank(),
			"prestige" => $this->getPrestige(),
			"playtime" => $this->getPlaytime() + $this->getAddedPlaytime()
		];
	}

	public function applySerializedData(array $data): void {
		$this->rank = $data["rank"];
		$this->prestige = $data["prestige"];
		$this->playtime = $data["playtime"];
	}
}
