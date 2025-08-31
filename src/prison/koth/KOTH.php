<?php namespace prison\koth;

use pocketmine\Server;
use pocketmine\entity\{
	EntityDataHelper,
	EntityFactory
};
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\world\World;

use prison\Prison;
use prison\PrisonPlayer;
use prison\koth\commands\KothCommand;
use prison\koth\entity\CenterCrystal;

use core\Core;
use core\network\protocol\ServerSubUpdatePacket;
use core\utils\TextFormat;

class KOTH{

	public array $games = [];

	public function __construct(public Prison $plugin){
		$plugin->getServer()->getCommandMap()->register("koth", new KothCommand($plugin, "koth", "King of the hill command"));

		EntityFactory::getInstance()->register(CenterCrystal::class, function(World $world, CompoundTag $nbt) : CenterCrystal{
			return new CenterCrystal(EntityDataHelper::parseLocation($nbt, $world), null);
		}, ["minecraft:end_crystal", "CenterCrystal"]);

		$this->setupGames();
	}

	public function setupGames() : void{
		foreach(Structure::GAMES as $id => $data){
			$level = $data["level"];
			if(!$this->plugin->getServer()->getWorldManager()->isWorldLoaded($level)){
				$this->plugin->getServer()->getWorldManager()->loadWorld($level, true);
			}
			$this->games[$id] = new Game($id, $data["name"], $level, $data["time"], $data["corners"], $this->setupPositions($data["spawnpoints"]), new Vector3(...$data["center"]), $data["distance-to-collect"], ($data["glass"] ?? []));
		}
		if(!Core::thisServer()->isSubServer()){
			foreach($this->getGames() as $game){
				$game->end(true);
			}
		}
	}

	public function setupPositions(array $positions) : array{
		foreach($positions as $key => $array){
			$positions[$key] = new Vector3(...$array);
		}
		return $positions;
	}

	public function tick() : void{
		foreach($this->getActiveGames() as $id => $game){
			$game->tick();
		}
	}

	public function close() : void{
		if(!Core::thisServer()->isSubServer()){
			foreach($this->getActiveGames() as $game){
				$game->end(true);
				echo "ended game " . $game->getId(), PHP_EOL;
			}
			echo "not subserver", PHP_EOL;
		}else{
			echo "subserver", PHP_EOL;
		}
	}

	public function getGames() : array{
		return $this->games;
	}

	public function getRandomGame() : Game{
		return $this->games[array_rand($this->games)];
	}

	public function getGameById(string $id) : ?Game{
		return $this->games[$id] ?? null;
	}

	public function getGameByName(string $name) : ?Game{
		foreach($this->getGames() as $id => $game){
			if(strtolower($game->getName()) == strtolower($name)){
				return $game;
			}
		}
		return null;
	}

	public function getActiveGames() : array{
		$games = [];
		foreach($this->getGames() as $id => $game){
			if($game->isActive()) $games[$id] = $game;
		}
		return $games;
	}

	public function inGame(Player $player) : bool{
		/** @var PrisonPlayer $player */
		return $player->getGameSession()->getKoth()->inGame();
	}

	public function getGameByPlayer(Player $player) : ?Game{
		/** @var PrisonPlayer $player */
		return $player->getGameSession()->getKoth()->getGame();
	}

	public function startKoth(string $name = "", bool $alert = true) : bool{
		$game = ($name === "" ? $this->getRandomGame() : $this->getGameById($name));
		if($game === null){
			return false;
		}

		if($game->isActive()){
			return false;
		}

		$game->setActive();

		if($alert){
			Server::getInstance()->broadcastMessage($message = TextFormat::GI . TextFormat::LIGHT_PURPLE . "A KOTH event has started! " . TextFormat::YELLOW . "/koth tp " . TextFormat::AQUA . $game->getName());
		}

		$servers = [];
		foreach(Core::thisServer()->getSubServers(false, true) as $server){
			$servers[] = $server->getIdentifier();
		}
		(new ServerSubUpdatePacket([
			"server" => $servers,
			"type" => "koth",
			"data" => [
				"started" => true,
				"gameId" => $game->getId(),
				"message" => $alert ? $message : ""
			]
		]))->queue();
		return true;
	}

	public function getHudFormat() : string{
		if(empty($this->getActiveGames())) return "";

		return TextFormat::GRAY . "KOTH event started. " . TextFormat::YELLOW . "/koth tp";
	}

}