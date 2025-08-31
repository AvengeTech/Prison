<?php namespace prison\blocktournament\uis;

use pocketmine\player\Player;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use core\utils\TextFormat;

class CommandHelpUi extends SimpleForm{

	const HELP = [
		"create" => [
			"arguments" => [],
			"alias" => ["c", "new", "n", "start"],
			"description" => "Opens the menu to start a Block Tournament"
		],
		"edit" => [
			"arguments" => [],
			"alias" => ["e", "manage", "m"],
			"description" => "Manage invites, or participating players in your Block Tournament"
		],
		"stop" => [
			"arguments" => ["s", "cancel"],
			"alias" => [],
			"description" => "Opens menu to cancel your Block Tournament"
		],


		"details" => [
			"arguments" => [],
			"alias" => ["detail", "dt"],
			"description" => "See the details of the current Block Tournament you are in"
		],
		"results" => [
			"arguments" => [],
			"alias" => ["result", "r"],
			"description" => "View the results of the last Block Tournament you participated in"
		],

		"join" => [
			"arguments" => [],
			"alias" => ["j"],
			"description" => "Joins the public Block Tournament (if one exists)"
		],
		"invites" => [
			"arguments" => [],
			"alias" => ["invite", "i"],
			"description" => "View all invites you've received to Block Tournaments"
		],
		"drop" => [
			"arguments" => [],
			"alias" => ["quit", "q", "leave"],
			"description" => "Leave the Block Tournament you are in"
		],

		"mod" => [
			"staff" => true,
			"arguments" => ["[game]"],
			"alias" => ["staff"],
			"description" => "Moderate all Block Tournaments"
		],

	];

	public function __construct(Player $player){
		/** @var PrisonPlayer $player */
		parent::__construct("Block Tournaments",
			"How it works:" . PHP_EOL . PHP_EOL .

			"You will be given " . TextFormat::YELLOW . "60 seconds" . TextFormat::WHITE . " to prepare before a Block Tournament starts. Once it starts, mine as MANY blocks as you can from the mines before the timer runs out to win!" . PHP_EOL . PHP_EOL .
			"You can see the status of the game, what place you're in, how many blocks you've mined, and how much time is left in a game below the Boss Bar at the top of your screen, when you're in a game of course." . PHP_EOL . PHP_EOL .

			"Select an option below to learn more!"
		);
		foreach(self::HELP as $name => $data){
			if((!($data["staff"] ?? false)) || $player->isStaff()) $this->addButton(new Button(TextFormat::RED . "/bt " . $name));
		}
	}

	public function handle($response, Player $player){
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