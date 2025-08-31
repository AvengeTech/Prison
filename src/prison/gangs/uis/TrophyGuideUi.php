<?php namespace prison\gangs\uis;

use pocketmine\player\Player;

use prison\gangs\objects\TrophyData;
use prison\PrisonPlayer;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class TrophyGuideUi extends SimpleForm{

	const GUIDES = [
		[
			"name" => "General Trophy Collecting",
			"description" =>
				"By killing a player (in Mine PvP or by /pvp), you have a " . TextFormat::YELLOW . TrophyData::PERCENT_KILLS . " percent (+" . TrophyData::PERCENT_KILLS_MEMBER . " per gang member) chance" . TextFormat::WHITE . " to earn " . TextFormat::GOLD . TrophyData::EVENT_KILL . " trophies" . TextFormat::WHITE . " for your gang!" . PHP_EOL . PHP_EOL .
				"If you earn the trophies for killing a player, a " . TextFormat::AQUA . "30 minute cooldown" . TextFormat::WHITE . " is activated, where you can't earn trophies from killing this player again until the cooldown runs out." . PHP_EOL . PHP_EOL .
				"You can die and lose trophies for your gang up to " . TextFormat::AQUA . "3 times " . TextFormat::WHITE . "from the same player before a server restart. Unlike kills, trophies lost by death occur " . TextFormat::RED . "100 percent" . TextFormat::WHITE . " of the time... So be careful!" . PHP_EOL . PHP_EOL .
				"Trophies are also earned by mining in the mines! For every " . TextFormat::AQUA . "500 blocks " . TextFormat::WHITE . "your gang mines, you have a " . TextFormat::YELLOW . TrophyData::PERCENT_BLOCK_BREAK . " percent (+" . TrophyData::PERCENT_BLOCK_BREAK_LEVEL . " each gang level) chance" . TextFormat::WHITE . " of earning " . TextFormat::GOLD . TrophyData::EVENT_BLOCK_BREAK . " trophy! " . TextFormat::WHITE . "A gang can earn a total of " . TextFormat::GOLD . TrophyData::MAX_BLOCK_BREAK . " trophies " . TextFormat::WHITE . "from block mining each server restart."
		],
		[
			"name" => "Gang Battles",
			"description" =>
				"Gang battles is a fast-paced gamemode, and your best bet for earning trophies for your gang!" . PHP_EOL . PHP_EOL .
				"When a gang wins a battle, it earns " . TextFormat::GOLD . TrophyData::EVENT_BATTLE_WIN . " trophies" . TextFormat::WHITE . " automatically, plus an additional " . TextFormat::GOLD . TrophyData::EVENT_BATTLE_KILL . " trophies" . TextFormat::WHITE . " per each kill your gang receives! (" . TrophyData::MAX_BATTLE_KILL . " extra trophies max)" . PHP_EOL . PHP_EOL .
				"When a gang loses a battle, it loses " . TextFormat::GOLD . TrophyData::EVENT_BATTLE_LOSE . " trophies" . TextFormat::WHITE . ", and earns " . TextFormat::RED . "ZERO" . TextFormat::WHITE . " for kills." . PHP_EOL . PHP_EOL .
				"If your gang has already recently battled an opposing gang within the past hour, neither gangs will earn any trophies or battle stats"
		],
		[
			"name" => "Alliances",
			"description" => "You can NOT earn any trophies by killing members of an allied gang, nor can you win any trophies by winning a battle with an allied gang!"
		]
	];

	public function __construct(){
		parent::__construct("Gang Trophy Guide",
			"Trophies are amazing! They're shiny, golden, and can help your gang out in a couple of ways!" . PHP_EOL . PHP_EOL .

			"One of those uses are gang upgrades. To see how many trophies and how many techits you need in your gang bank to upgrade your gang, type " . TextFormat::YELLOW . "/gang upgrade" . TextFormat::WHITE . ". By upgrading your gang, you can unlock more slots for gang members to join, and more Gang Shops!" . PHP_EOL . PHP_EOL .
			"The second reason to collect trophies is purchasing from the gang shop. Only gang elders and leaders can access this shop, because the currency is gang trophies! But you can access a variety of useful items here." . PHP_EOL . PHP_EOL .

			"Tap a button below to learn more about trophies!"
		);
		foreach(self::GUIDES as $guide){
			$this->addButton(new Button(TextFormat::AQUA . $guide["name"]));
		}
		$this->addButton(new Button("Go back"));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$guide = self::GUIDES[$response] ?? [];
		if(empty($guide)){
			$player->showModal(new CommandHelpUi($player));
			return;
		}
		$player->showModal(new TrophyGuideExUi($guide["name"], $guide["description"]));
	}

}