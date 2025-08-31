<?php namespace prison\rankup;

use pocketmine\player\Player;

use prison\rankup\commands\{
	RankUpCommand,
	PrestigeCommand,
	PlaytimeCommand,
    RankUpMaxCommand
};
use prison\Prison;

use core\utils\{
	TextFormat,
};

class RankUp{

	const PLAYTIME_COOLDOWN = 30;

	const PRICES = [
		"b" => 2000,
		"c" => 5000,
		"d" => 10000,
		"e" => 25000,
		"f" => 50000,
		"g" => 75000,
		"h" => 100000,
		"i" => 110000,
		"j" => 125000,
		"k" => 130000,
		"l" => 150000,
		"m" => 175000,
		"n" => 200000,
		"o" => 250000,
		"p" => 300000,
		"q" => 600000,
		"r" => 900000,
		"s" => 1000000,
		"t" => 1250000,
		"u" => 1500000,
		"v" => 1500000,
		"w" => 1750000,
		"x" => 1750000,
		"y" => 2000000,
		"z" => 2500000,
		"free" => 3000000,
	];
	
	public array $cooldown = [];

	public function __construct(public Prison $plugin){
		$plugin->getServer()->getCommandMap()->registerAll("rankup", [
			new RankUpCommand($plugin, "rankup", "Rank up your prison rank!"),
			new RankUpMaxCommand($plugin, "rankupmax", "Reach your highest your prison rank!"),
			new PrestigeCommand($plugin, "prestige", "Level up your prestige!"),
			new PlaytimeCommand($plugin, "playtime", "See how long you have been playing!"),
		]);
	}

	public function getRankUpPrice(string $rank) : int{
		return self::PRICES[$rank] ?? 3000000;
	}

	public function getNextRank(string $rank) : string{
		if($rank == "z") return "free";
		if($rank == "free") return -1;
		$rank++;
		return $rank;
	}

	public function getRankColor(string $rank = "a") : string{
		$key = ord(strtoupper($rank)) - ord('A') + 1;
		if($rank == "free"){
			$color = TextFormat::YELLOW;
		}else{
			if($key <= 5){
				$color = TextFormat::BLUE;
			}elseif($key > 5 && $key <= 10){
				$color = TextFormat::AQUA;
			}elseif($key > 10 && $key <= 15){
				$color = TextFormat::DARK_PURPLE;
			}elseif($key > 15 && $key <= 20){
				$color = TextFormat::RED;
			}elseif($key > 20 && $key <= 26){
				$color = TextFormat::GOLD;
			}
		}
		return $color;
	}

	public function getFormattedRank(string $rank = "a", int $prestige = 0) : string{
		return TextFormat::WHITE . "[" . ($prestige > 0 ? TextFormat::BOLD . TextFormat::GREEN . $prestige . TextFormat::RESET : "") . $this->getRankColor($rank) . strtoupper($rank) . TextFormat::WHITE . "]";
	}

	public function hasCooldown(Player $player) : bool{
		return $this->getCooldown($player) > 0;
	}

	public function getCooldown(Player $player) : int{
		return ($this->cooldown[$player->getName()] ?? 0) - time();
	}

	public function setCooldown(Player $player) : void{
		$this->cooldown[$player->getName()] = time() + self::PLAYTIME_COOLDOWN;
	}

}