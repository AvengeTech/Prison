<?php namespace prison\mines;

use pocketmine\player\{
	GameMode,
	Player
};

use prison\Prison;
use prison\PrisonPlayer;

use core\session\component\{
	ComponentRequest,
	SaveableComponent
};
use core\session\mysqli\data\MySqlQuery;
use core\utils\TextFormat;

class MinesComponent extends SaveableComponent{

	const RESET_COOLDOWN = 30;

	public array $mined = [
		"a" => 0,
		"b" => 0,
		"c" => 0,
		"d" => 0,
		"e" => 0,
		"f" => 0,
		"g" => 0,
		"h" => 0,
		"i" => 0,
		"j" => 0,
		"k" => 0,
		"l" => 0,
		"m" => 0,
		"n" => 0,
		"o" => 0,
		"p" => 0,
		"q" => 0,
		"r" => 0,
		"s" => 0,
		"t" => 0,
		"u" => 0,
		"v" => 0,
		"w" => 0,
		"x" => 0,
		"y" => 0,
		"z" => 0,

		"vip" => 0,
		"pvp" => 0,
		"vote" => 0,

		"p1" => 0,
		"p5" => 0,
		"p10" => 0,
		"p15" => 0,
		"p20" => 0,
		"p25" => 0,
	];
	
	public string $mine = "";
	public int $streak = 0;

	public int $lastReset = 0;
	
	public function getName() : string{
		return "mines";
	}

	public function getMinedBlocks(string $mine) : int{
		return $this->mined[$mine] ?? 0;
	}

	public function addMinedBlock(string $mine, int $amount = 1) : void{
		$this->mined[$mine] += $amount;
		$this->setChanged();
	}

	public function getUnlockedMines() : array{
		/** @var PrisonPlayer $player */
		$player = $this->getPlayer();

		$rank = ($rs = $player->getGameSession()->getRankUp())->getRank();
		$prestige = $rs->getPrestige();

		if($rank == "free") $rank = "z";
		$unlocked = [];
		if($player->getRank() != "default") $unlocked[] = "vip";
		$unlocked = array_merge($unlocked, range("a", $rank));
		$unlocked[] = "pvp";

		foreach([1, 5, 10, 15, 20, 25] as $p){
			if($prestige >= $p) $unlocked[] = "p" . $p;
		}

		return $unlocked;
	}

	public function canTeleportTo(string $mine) : bool{
		if($this->getPlayer()->getName() == "sn3akrr") return true;
		$unlocked = $this->getUnlockedMines();
		return in_array($mine, $unlocked);
	}

	public function inMine() : bool{
		return $this->mine != "";
	}

	public function getMineLetter() : string{
		return $this->mine ?? "";
	}

	public function getMine(string $letter = "") : ?Mine{
		return Prison::getInstance()->getMines()->getMineByName(($letter == "" ? $this->getMineLetter() : $letter));
	}

	public function getStreak() : int{
		return $this->streak;
	}

	public function addStreak() : void{
		$this->streak++;
	}

	public function resetStreak() : void{
		$this->streak = 0;
	}

	public function enterMine(string $mine, int $hierarchy = 0) : void{
		$player = $this->getPlayer();

		$minee = $this->getMine($mine);
		$minee->teleportTo($player);
		$this->mine = $mine;

		$player->setAllowFlight(false);
		if($player->inFlightMode()) $player->setFlightMode(false);

		if(!$this->canTeleportTo($mine) && $hierarchy >= 8){
			$this->mine = "";
			$player->sendMessage(TextFormat::RN . "Mine not unlocked. You may not break blocks here.");
			$player->setGamemode(GameMode::ADVENTURE());
			return;
		}
		$player->setGamemode(GameMode::SURVIVAL());
	}

	public function exitMine(bool $teleport = true) : void{
		/** @var PrisonPlayer $player */
		$player = $this->getPlayer();

		$this->mine = "";
		$this->resetStreak();

		if($teleport){
			$player->gotoSpawn();
			$player->setGamemode(GameMode::ADVENTURE());
			if(!$player->getGameSession()->getCombat()->inPvPMode()) $player->setAllowFlight(true);
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

	public function createTables() : void{
		$db = $this->getSession()->getSessionManager()->getDatabase();
		foreach([
			"CREATE TABLE IF NOT EXISTS mines_total(
				xuid BIGINT(16) NOT NULL UNIQUE, 
				a INT NOT NULL DEFAULT 0, 
				b INT NOT NULL DEFAULT 0, 
				c INT NOT NULL DEFAULT 0, 
				d INT NOT NULL DEFAULT 0, 
				e INT NOT NULL DEFAULT 0, 
				f INT NOT NULL DEFAULT 0, 
				g INT NOT NULL DEFAULT 0, 
				h INT NOT NULL DEFAULT 0, 
				i INT NOT NULL DEFAULT 0, 
				j INT NOT NULL DEFAULT 0, 
				k INT NOT NULL DEFAULT 0, 
				l INT NOT NULL DEFAULT 0, 
				m INT NOT NULL DEFAULT 0, 
				n INT NOT NULL DEFAULT 0, 
				o INT NOT NULL DEFAULT 0, 
				p INT NOT NULL DEFAULT 0, 
				q INT NOT NULL DEFAULT 0, 
				r INT NOT NULL DEFAULT 0, 
				s INT NOT NULL DEFAULT 0, 
				t INT NOT NULL DEFAULT 0, 
				u INT NOT NULL DEFAULT 0, 
				v INT NOT NULL DEFAULT 0, 
				w INT NOT NULL DEFAULT 0, 
				x INT NOT NULL DEFAULT 0, 
				y INT NOT NULL DEFAULT 0, 
				z INT NOT NULL DEFAULT 0,

				vip INT NOT NULL DEFAULT 0,
				pvp INT NOT NULL DEFAULT 0,
				vote INT NOT NULL DEFAULT 0,

				p1 INT NOT NULL DEFAULT 0,
				p5 INT NOT NULL DEFAULT 0,
				p10 INT NOT NULL DEFAULT 0,
				p15 INT NOT NULL DEFAULT 0,
				p20 INT NOT NULL DEFAULT 0,
				p25 INT NOT NULL DEFAULT 0
			)",
		] as $query) $db->query($query);
	}

	public function loadAsync() : void{
		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main", "SELECT * FROM mines_total WHERE xuid=?", [$this->getXuid()]));
		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null) : void{
		$result = $request->getQuery()->getResult();
		$rows = (array) $result->getRows();
		if(count($rows) > 0){
			$data = array_shift($rows);
			foreach($this->mined as $mine => $value){
				$this->mined[$mine] = $data[$mine] ?? 0;
			}
		}

		parent::finishLoadAsync($request);
	}

	public function verifyChange() : bool{
		$verify = $this->getChangeVerify();
		return $this->mined !== $verify["mined"];
	}

	public function saveAsync() : void{
		if(!$this->hasChanged() || !$this->isLoaded()) return;

		$this->setChangeVerify([
			"mined" => $this->mined,
		]);

		$player = $this->getPlayer();
		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main",
			"INSERT INTO mines_total(xuid,
			a, b, c, d, e, f, g, h, i, j,
			k, l, m, n, o, p, q, r, s, t,
			u, v, w, x, y, z,
			vip, pvp, vote,
			p1, p5, p10, p15, p20, p25) VALUES(?,
			?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
			?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
			?, ?, ?, ?, ?, ?,
			?, ?, ?,
			?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE 
			a=VALUES(a), b=VALUES(b), c=VALUES(c), d=VALUES(d),
			e=VALUES(e), f=VALUES(f), g=VALUES(g), h=VALUES(h),
			i=VALUES(i), j=VALUES(j), k=VALUES(k), l=VALUES(l),
			m=VALUES(m), n=VALUES(n), o=VALUES(o), p=VALUES(p),
			q=VALUES(q), r=VALUES(r), s=VALUES(s), t=VALUES(t),
			u=VALUES(u), v=VALUES(v), w=VALUES(w), x=VALUES(x),
			y=VALUES(y), z=VALUES(z),
			vip=VALUES(vip), pvp=VALUES(pvp), vote=VALUES(vote),
			p1=VALUES(p1), p5=VALUES(p5), p10=VALUES(p10),
			p15=VALUES(p15), p1=VALUES(p20), p25=VALUES(p25)",
			array_merge([$this->getXuid()], array_values($this->mined))
		));
		$this->newRequest($request, ComponentRequest::TYPE_SAVE);
		parent::saveAsync();
	}

	public function save() : bool{
		if(!$this->hasChanged() || !$this->isLoaded()) return false;

		$db = $this->getSession()->getSessionManager()->getDatabase();
		$xuid = $this->getXuid();

		for($ptr = ord("a"), $end = ord("z"); $ptr <= $end; ++$ptr){
			${chr($ptr)} = $this->mined[chr($ptr)] ?? null;
		}
		$vip = $this->mined["vip"];
		$pvp = $this->mined["pvp"];
		$vote = $this->mined["vote"];

		$p1 = $this->mined["p1"];
		$p5 = $this->mined["p5"];
		$p10 = $this->mined["p10"];
		$p15 = $this->mined["p15"];
		$p20 = $this->mined["p20"];
		$p25 = $this->mined["p25"];

		$stmt = $db->prepare("INSERT INTO mines_total(xuid,
			a, b, c, d, e, f, g, h, i, j,
			k, l, m, n, o, p, q, r, s, t,
			u, v, w, x, y, z,
			vip, pvp, vote,
			p1, p5, p10, p15, p20, p25) VALUES(?,
			?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
			?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
			?, ?, ?, ?, ?, ?,
			?, ?, ?,
			?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE 
			a=VALUES(a), b=VALUES(b), c=VALUES(c), d=VALUES(d),
			e=VALUES(e), f=VALUES(f), g=VALUES(g), h=VALUES(h),
			i=VALUES(i), j=VALUES(j), k=VALUES(k), l=VALUES(l),
			m=VALUES(m), n=VALUES(n), o=VALUES(o), p=VALUES(p),
			q=VALUES(q), r=VALUES(r), s=VALUES(s), t=VALUES(t),
			u=VALUES(u), v=VALUES(v), w=VALUES(w), x=VALUES(x),
			y=VALUES(y), z=VALUES(z),
			vip=VALUES(vip), pvp=VALUES(pvp), vote=VALUES(vote),
			p1=VALUES(p1), p5=VALUES(p5), p10=VALUES(p10),
			p15=VALUES(p15), p1=VALUES(p20), p25=VALUES(p25)
		");

		$stmt->bind_param("iiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii", $xuid,
			$a, $b, $c, $d, $e, $f, $g, $h, $i, $j,
			$k, $l, $m, $n, $o, $p, $q, $r, $s, $t,
			$u, $v, $w, $x, $y, $z, $vip, $pvp, $vote,
			$p1, $p5, $p10, $p15, $p20, $p25
		);

		$stmt->execute();
		$stmt->close();

		return parent::save();
	}

	public function getSerializedData(): array {
		for ($ptr = ord("a"), $end = ord("z"); $ptr <= $end; ++$ptr) {
			${chr($ptr)} = $this->mined[chr($ptr)] ?? null;
		}
		$vip = $this->mined["vip"];
		$pvp = $this->mined["pvp"];
		$vote = $this->mined["vote"];

		$p1 = $this->mined["p1"];
		$p5 = $this->mined["p5"];
		$p10 = $this->mined["p10"];
		$p15 = $this->mined["p15"];
		$p20 = $this->mined["p20"];
		$p25 = $this->mined["p25"];

		return [
			"a" => $a,
			"b" => $b,
			"c" => $c,
			"d" => $d,
			"e" => $e,
			"f" => $f,
			"g" => $g,
			"h" => $h,
			"i" => $i,
			"j" => $j,
			"k" => $k,
			"l" => $l,
			"m" => $m,
			"n" => $n,
			"o" => $o,
			"p" => $p,
			"q" => $q,
			"r" => $r,
			"s" => $s,
			"t" => $t,
			"u" => $u,
			"v" => $v,
			"w" => $w,
			"x" => $x,
			"y" => $y,
			"z" => $z,
			"vip" => $vip,
			"pvp" => $pvp,
			"vote" => $vote,
			"p1" => $p1,
			"p5" => $p5,
			"p10" => $p10,
			"p15" => $p15,
			"p20" => $p20,
			"p25" => $p25
		];
	}

	public function applySerializedData(array $data): void {
		foreach ($this->mined as $mine => $value) {
			$this->mined[$mine] = $data[$mine] ?? 0;
		}
	}

}