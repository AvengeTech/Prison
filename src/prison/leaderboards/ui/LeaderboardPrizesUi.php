<?php namespace prison\leaderboards\ui;

use core\Core;
use core\ui\windows\SimpleForm;
use core\utils\TextFormat;

class LeaderboardPrizesUi extends SimpleForm{

	public function __construct(){
		parent::__construct(
			"Leaderboard Prizes",
			(Core::thisServer()->isTestServer() ? TextFormat::EMOJI_CAUTION . TextFormat::YELLOW . " NOTE: You cannot claim any paypal or store credit prizes from the test server." . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") .
			"Some of our leaderboards reset " . TextFormat::AQUA . "monthly" . TextFormat::WHITE . ". Stay on these leaderboards to get prizes when they reset!" . PHP_EOL . PHP_EOL .
			TextFormat::AQUA . "Monthly leaderboards (Reset every 1st of the month):" . TextFormat::WHITE . PHP_EOL . PHP_EOL .

			TextFormat::EMOJI_SKULL . " PvP Mine kills:" . PHP_EOL .
			"1. " . TextFormat::AQUA . "1,000,000 techits" . TextFormat::WHITE . " + " . TextFormat::GREEN . "Discord Nitro Classic OR $5 store credit" . TextFormat::WHITE . PHP_EOL .
			"2. " . TextFormat::AQUA . "1,000,000 techits" . TextFormat::WHITE . PHP_EOL .
			"3. " . TextFormat::AQUA . "750,000 techits" . TextFormat::WHITE . PHP_EOL .
			"4. " . TextFormat::AQUA . "500,000 techits" . TextFormat::WHITE . PHP_EOL .
			"5. " . TextFormat::AQUA . "250,000 techits" . TextFormat::WHITE . PHP_EOL . PHP_EOL .

			TextFormat::EMOJI_SKULL . " KOTH kills:" . PHP_EOL .
			"1. " . TextFormat::AQUA . "1,000,000 techits" . TextFormat::WHITE . " + " . TextFormat::GREEN . "$10 PayPal" . TextFormat::WHITE . PHP_EOL .
			"2. " . TextFormat::AQUA . "1,000,000 techits" . TextFormat::WHITE . " + " . TextFormat::GREEN . "$10 Store credit" . TextFormat::WHITE . PHP_EOL .
			"3. " . TextFormat::AQUA . "750,000 techits" . TextFormat::WHITE . PHP_EOL .
			"4. " . TextFormat::AQUA . "500,000 techits" . TextFormat::WHITE . PHP_EOL .
			"5. " . TextFormat::AQUA . "250,000 techits" . TextFormat::WHITE . PHP_EOL . PHP_EOL .
			
			TextFormat::EMOJI_TROPHY . " KOTH wins:" . PHP_EOL .
			"1. " . TextFormat::AQUA . "2,500,000 techits" . TextFormat::WHITE . " + " . TextFormat::GREEN . "$10 PayPal" . TextFormat::WHITE . PHP_EOL .
			"2. " . TextFormat::AQUA . "1,000,000 techits" . TextFormat::WHITE . " + " . TextFormat::GREEN . "$10 Store credit" . TextFormat::WHITE . PHP_EOL .
			"3. " . TextFormat::AQUA . "750,000 techits" . TextFormat::WHITE . PHP_EOL .
			"4. " . TextFormat::AQUA . "500,000 techits" . TextFormat::WHITE . PHP_EOL .
			"5. " . TextFormat::AQUA . "250,000 techits" . TextFormat::WHITE . PHP_EOL . PHP_EOL .

				"(NOTE: To claim " . TextFormat::LIGHT_PURPLE . "Nitro Classic" . TextFormat::WHITE . " + " . TextFormat::GREEN . "store credit" . TextFormat::WHITE . " prizes, you MUST be in our " . TextFormat::BLUE . "Discord server" . TextFormat::WHITE . ". Join at " . TextFormat::YELLOW . "avengetech.net/discord" . TextFormat::WHITE . ")" . PHP_EOL . PHP_EOL .

			"All techit prizes will automatically be sent to your inbox."
		);
	}

}