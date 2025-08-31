<?php namespace prison\gangs\uis;

use pocketmine\player\Player;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

use prison\PrisonPlayer;

class CommandHelpUi extends SimpleForm{

	const HELP = [
		"create" => [
			"arguments" => [],
			"alias" => [],
			"description" => "Opens the menu to create a new gang"
		],
		"leave" => [
			"arguments" => [],
			"alias" => [],
			"description" => "Leave the gang you are currently in"
		],
		"delete" => [
			"arguments" => [],
			"alias" => ["del", "genocide"],
			"description" => "Delete the gang you are in (LEADER only)"
		],

		"description" => [
			"arguments" => [],
			"alias" => ["d"],
			"description" => "Edit gang description (CO-LEADER and up)"
		],


		"info" => [
			"arguments" => [],
			"alias" => ["details", "stats"],
			"description" => "View information about your gang"
		],
		"player" => [
			"arguments" => ["<player>"],
			"alias" => ["pinfo"],
			"description" => "View information about another player's gang"
		],

		"invite" => [
			"arguments" => ["[player]"],
			"alias" => ["inv"],
			"description" => "Invite a member to your gang (ELDER and up)"
		],
		"invites" => [
			"arguments" => [],
			"alias" => [],
			"description" => "View all invites you've received from gangs"
		],

		"kick" => [
			"arguments" => [],
			"alias" => [],
			"description" => "Kick a member from your gang (ELDER and up)"
		],
		"promote" => [
			"arguments" => [],
			"alias" => [],
			"description" => "Promote a member from your gang to elder (CO-LEADER and up)"
		],
		"demote" => [
			"arguments" => [],
			"alias" => [],
			"description" => "Demote a member from your gang to member (CO-LEADER and up)"
		],

		"chat" => [
			"arguments" => ["<type=(off [n], gang [g], ally [a])>"],
			"alias" => ["c"],
			"description" => "Enter gang or ally chat mode"
		],

		"bank" => [
			"arguments" => [],
			"alias" => [],
			"description" => "Open's your gang's bank"
		],

		"upgrade" => [
			"arguments" => [],
			"alias" => ["u"],
			"description" => "Upgrade your gang (CO-LEADER and up)"
		],

		"shop" => [
			"arguments" => [],
			"alias" => ["market"],
			"description" => "Opens the gang shop"
		],

		"ally" => [
			"arguments" => [],
			"alias" => ["a", "alliance", "allies", "alliances"],
			"description" => "Opens the gang alliance menu",
		],

		"battle" => [
			"arguments" => [],
			"alias" => ["b"],
			"description" => "Leaders can use this to send/manage battle requests. Gang members can also use this to enter a new gang battle once started.",
		],
		"battles" => [
			"arguments" => ["spectate", "spec"],
			"alias" => [],
			"description" => "Spectate ongoing battles!"
		],
		"results" => [
			"arguments" => [],
			"alias" => ["r"],
			"description" => "View results of your gang's recently finished battles"
		],

		"help" => [
			"arguments" => [],
			"alias" => [],
			"description" => "View all gang commands and information about them"
		],

	];

	public function __construct(Player $player) {
		/** @var PrisonPlayer $player */
		parent::__construct("Gangs Help Page",
			"Select an option below to learn more!"
		);
		$this->addButton(new Button(TextFormat::BOLD . TextFormat::DARK_RED . "TROPHY GUIDE"));
		foreach(self::HELP as $name => $data){
			if((!($data["staff"] ?? false)) || $player->isStaff()) $this->addButton(new Button(TextFormat::RED . "/gang " . $name));
		}
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if($response == 0){
			$player->showModal(new TrophyGuideUi($player));
			return;
		}
		$key = 0;
		foreach(self::HELP as $name => $entry){
			if($response - 1 == $key){
				$player->showModal(new CommandInfoUi($name, $entry));
				return;
			}
			$key++;
		}
	}

}