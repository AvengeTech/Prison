<?php

namespace prison\commands;

use core\AtPlayer;
use core\command\type\CoreCommand;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use pocketmine\player\Player;
use prison\Prison;
use prison\PrisonPlayer;
use core\discord\objects\{
	Post,
	Embed,
	Field,
	Footer,
	Webhook
};
use core\utils\TextFormat;
use core\Core;

class Pay extends CoreCommand {

	const PAY_COOLDOWN = 15;
	public static array $paycd = [];

	public function __construct(public Prison $plugin, $name, $description) {
		parent::__construct($name, $description);
		$this->setInGameOnly();
	}

	/**
	 * @param PrisonPlayer $sender
	 */
	public function handlePlayer(AtPlayer $sender, string $commandLabel, array $args) {
		if (count($args) != 2) {
			$sender->sendMessage(TextFormat::RN . "Usage: /pay <name> <amount>");
			return false;
		}
		$player = $this->plugin->getServer()->getPlayerExact(array_shift($args));
		$techits = (int) array_shift($args);
		if (!$player instanceof PrisonPlayer || !$player->isLoadedP()) {
			$sender->sendMessage(TextFormat::RN . "Player not online! You can only pay online players!");
			return false;
		}
		$ptechits = $sender->getTechits();
		if (isset(self::$paycd[$sender->getName()])) {
			if (($left = self::$paycd[$sender->getName()] - time()) > 0) {
				$sender->sendMessage(TextFormat::RI . "You must wait " . TextFormat::YELLOW . $left . TextFormat::GRAY . " more seconds to send another payment!");
				return;
			}
		}
		if ($techits <= 0) {
			$sender->sendMessage(TextFormat::RN . "You cannot pay players under 0 techits!");
			return false;
		}
		if ($techits > $ptechits) {
			$sender->sendMessage(TextFormat::RN . "You do not have enough techits to pay this amount!");
			return false;
		}
		$before = $player->getTechits();
		$player->addTechits($techits);
		$after = $player->getTechits();
		$sbefore = $sender->getTechits();
		$sender->takeTechits($techits);
		$safter = $sender->getTechits();
		self::$paycd[$sender->getName()] = time() + self::PAY_COOLDOWN;
		$player->sendMessage(TextFormat::GN . "You received " . TextFormat::AQUA . number_format($techits) . " techits" . TextFormat::GRAY . " from " . TextFormat::YELLOW . $sender->getName());
		$sender->sendMessage(TextFormat::GN . "Successfully sent " . TextFormat::AQUA . number_format($techits) . " techits" . TextFormat::GRAY . " to " . TextFormat::YELLOW . $player->getName());
		$server = \core\Core::getInstance()->getNetwork()->getServerManager()->getThisServer();
		$post = new \core\discord\objects\Post("", "Pay Log - " . \core\Core::getInstance()->getNetwork()->getIdentifier(), "[REDACTED]", false, "", [
			new \core\discord\objects\Embed("", "rich", "**" . $sender->getName() . "** just sent **" . number_format($techits) . " techits** to **" . $player->getName() . "**", "", "ffb106", new \core\discord\objects\Footer("Cheese! | " . date("F j, Y, g:ia", time())), "", "[REDACTED]", null, [
				new \core\discord\objects\Field($player->getName() . "'s balance", number_format($before) . " -> " . number_format($after), true),
				new \core\discord\objects\Field($sender->getName() . "'s balance", number_format($sbefore) . " -> " . number_format($safter), true),
			])
		]);
		$post->setWebhook(\core\discord\objects\Webhook::getWebhookByName("prison-paylog"));
		$post->send();
	}
}
