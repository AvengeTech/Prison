<?php namespace prison\mysteryboxes\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;

use core\Core;
use core\network\protocol\ServerSubUpdatePacket;
use core\utils\TextFormat;

class KeyAll extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.tier3");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		if($sender instanceof Player) {
			/** @var PrisonPlayer $sender */
			if($sender->getRank() != "owner"){
				$sender->sendMessage(TextFormat::RN . "You do not have permission to use this command");
				return;
			}
		}

		if(count($args) < 1){
			$sender->sendMessage(TextFormat::RN . "Usage: /keyall <type> [amount]");
			return;
		}

		$type = strtolower(array_shift($args));
		$amount = 1;
		if(isset($args[0])) $amount = (int) array_shift($args);

		if($amount <= 0 || $amount > 10){
			$sender->sendMessage(TextFormat::RN . "Amount must be a number between 1 and 10");
			return;
		}

		if(!in_array($type, ["iron", "gold", "diamond", "emerald", "divine", "vote"])){
			$sender->sendMessage(TextFormat::RN . "Invalid key type!");
			return;
		}

		$colors = [
			"iron" => TextFormat::WHITE,
			"gold" => TextFormat::GOLD,
			"diamond" => TextFormat::AQUA,
			"emerald" => TextFormat::GREEN,
			"divine" => TextFormat::RED,
			"vote" => TextFormat::YELLOW
		];
		foreach($this->plugin->getServer()->getOnlinePlayers() as $player) {
			/** @var PrisonPlayer $player */
			if($player->hasGameSession()){
				$player->sendMessage(TextFormat::GRAY . "Everyone online has received " . TextFormat::GREEN . "+" . $amount . " " . $colors[$type] . TextFormat::BOLD . strtoupper($type) . TextFormat::RESET . TextFormat::GREEN . " keys!");
				$session = $player->getGameSession()->getMysteryBoxes();
				$session->addKeys($type, $amount);
			}
		}

		$servers = [];
		foreach(Core::thisServer()->getSubServers(false, true) as $server){
			$servers[] = $server->getIdentifier();
		}
		(new ServerSubUpdatePacket([
			"server" => $servers,
			"type" => "keyall",
			"data" => [
				"type" => $type,
				"amount" => $amount,
			]
		]))->queue();
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}