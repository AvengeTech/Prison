<?php namespace prison\hud;

use pocketmine\Server;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\settings\PrisonSettings;

use core\utils\TextFormat;

class HudObject{

	public string $name = "";
	public string $text = "";

	public int $color = 5;

	public function __construct(Player $player){
		$this->name = $player->getName();
		$this->text = "{RUSEQ}" . PHP_EOL . PHP_EOL . "{KOTH}";
	}

	public function getName() : string{
		return $this->name;
	}

	public function getPlayer() : ?PrisonPlayer{
		return Server::getInstance()->getPlayerExact($this->getName());
	}

	public function getPercentage() : float{
		if(!($player = $this->getPlayer()) instanceof Player || !$player->isLoaded()) return 1;
		$techits = $this->getPlayer()->getTechits();
		$rankup = Prison::getInstance()->getRankUp();
		$rank = $this->getPlayer()->getGameSession()->getRankUp()->getRank();
		$ruprice = $rankup->getRankUpPrice($rankup->getNextRank($rank));

		return (($total = $techits / $ruprice) > 1 ? 1 : $total);
	}

	public function getText() : string{
		$text = $this->text;
		if(!($player = $this->getPlayer()) instanceof Player || !$player->isLoaded()) return "";
		$ru = Prison::getInstance()->getRankUp();
		$rank = $this->getPlayer()->getGameSession()->getRankUp()->getRank();

		if($rank == "free"){
			$text = str_replace("{RUSEQ}", $ru->getFormattedRank($rank) . TextFormat::GRAY . " | " . TextFormat::AQUA . "{TECHITS}", $text);
		}else{
			$newrank = $ru->getNextRank($rank);
			$text = str_replace("{RUSEQ}", "{RANK} " . TextFormat::GRAY . "-> {NEXTRANK} " . TextFormat::WHITE . "(" . TextFormat::AQUA . "{NEXTRANKCOST}" . TextFormat::WHITE . ")" . TextFormat::GRAY . " |" . TextFormat::YELLOW . " /rankup " . TextFormat::GRAY . "| " . TextFormat::AQUA . "{TECHITS}", $text);
			$text = str_replace("{RANK}", $ru->getFormattedRank($rank), $text);
			$text = str_replace("{NEXTRANK}", $ru->getFormattedRank($newrank), $text);
			$text = str_replace("{NEXTRANKCOST}", number_format($ru->getRankUpPrice($newrank)), $text);
		}
		$text = str_replace("{TECHITS}", number_format($this->getPlayer()->getTechits()), $text);
		$text = str_replace("{KOTH}", Prison::getInstance()->getKoth()->getHudFormat(), $text);

		return $text;
	}

	public function send() : void{
		$player = $this->getPlayer();
		if(!$player instanceof Player) return;

		$pk = new BossEventPacket();
		$pk->bossActorUniqueId = $player->getId();
		$pk->eventType = BossEventPacket::TYPE_SHOW;
		$pk->healthPercent = $this->getPercentage();
		$pk->title = $this->getText();
		$pk->filteredTitle = $this->getText();
		$pk->darkenScreen = false;
		$pk->overlay = 0;
		$pk->color = 5;
		
		$player->getNetworkSession()->sendDataPacket($pk);
	}

	public function sendDelayed(int $delay = 20) : void{
		$player = $this->getPlayer();
		$task = new class($player, $this) extends \pocketmine\scheduler\Task{
			public $player;
			public $hud;

			public function __construct(Player $player, HudObject $hud){
				$this->player = $player->getName();
				$this->hud = $hud;
			}

			public function onRun() : void{
				$player = Server::getInstance()->getPlayerExact($this->player);
				if($player instanceof Player && $player->isConnected()) $this->hud->send();
			}

		};
		Prison::getInstance()->getScheduler()->scheduleDelayedTask($task, $delay);
	}

	public function update() : void{
		$player = $this->getPlayer();
		if(!($player = $this->getPlayer()) instanceof Player || !$player->isLoaded()) return;
		
		$pk = new BossEventPacket();
		$pk->bossActorUniqueId = $player->getId();
		$pk->eventType = BossEventPacket::TYPE_TITLE;
		$pk->title = $this->getText();
		$pk->filteredTitle = $this->getText();
		$player->getNetworkSession()->sendDataPacket($pk);

		$pk = new BossEventPacket();
		$pk->bossActorUniqueId = $player->getId();
		$pk->eventType = BossEventPacket::TYPE_HEALTH_PERCENT;
		$pk->healthPercent = $percent = $this->getPercentage();
		$player->getNetworkSession()->sendDataPacket($pk);

		if($player->getGameSession()->getSettings()->getSetting(PrisonSettings::RAINBOW_BOSS_BAR)){
			$pk = new BossEventPacket();
			$pk->bossActorUniqueId = $player->getId();
			$pk->eventType = BossEventPacket::TYPE_TEXTURE;
			$pk->darkenScreen = false;
			$pk->overlay = 0;
			$pk->color = ($percent != 1 ? 5 : (++$this->color > 6 ? $this->color = 0 : $this->color));
			$player->getNetworkSession()->sendDataPacket($pk);	
		}
	}

}