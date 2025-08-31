<?php namespace prison\cells\ui;

use pocketmine\player\Player;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use core\utils\TextFormat;

use prison\PrisonPlayer;

class CommandHelpUi extends SimpleForm{

	const HELP = [
		"info" => [
			"arguments" => [],
			"alias" => ["about", "details", "i"],
			"description" => "View information about a cell"
		],
		"manage" => [
			"arguments" => [],
			"alias" => ["m"],
			"description" => "Manage your cell"
		],

		"style" => [
			"arguments" => [],
			"alias" => ["layout"],
			"description" => "Select a cell layout to apply"
		],
		"clearstyle" => [
			"arguments" => [],
			"alias" => ["clear", "cs"],
			"description" => "Clear your cell's current style"
		],

		"stores" => [
			"arguments" => [],
			"alias" => ["store"],
			"description" => "Open's a cell's storefront menu"
		],

		"managestores" => [
			"arguments" => [],
			"alias" => ["managestore", "ms"],
			"description" => "Manage stores of your cell"
		],

		"goto" => [
			"arguments" => [],
			"alias" => ["tp"],
			"description" => "Teleport to a specified cell"
		],

		"help" => [
			"arguments" => [],
			"alias" => [],
			"description" => "View all cell subcommands and information about them"
		],

	];

	public function __construct(Player $player) {
		/** @var PrisonPlayer $player */
		parent::__construct("Cells Help Page",
			"Type /cell inside of a cell to use a subcommand on it, or type /cell <subcommand> <player> to use on a specific player's cell! You can also use /mycell to automatically use your own cell" . PHP_EOL . PHP_EOL . 
			"Examples:" . PHP_EOL .
			"/cell info Sn3akPeak" . PHP_EOL . 
			"/mycell manage" . PHP_EOL . PHP_EOL . "Tap a subcommand below to learn more!"
		);
		foreach(self::HELP as $name => $data){
			if((!($data["staff"] ?? false)) || $player->isStaff()) $this->addButton(new Button(TextFormat::DARK_GREEN . "/cell " . $name));
		}
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$key = 0;
		foreach(self::HELP as $name => $entry){
			if($response == $key){
				$player->showModal(new CommandInfoUi($name, $entry));
				return;
			}
			$key++;
		}
	}

}