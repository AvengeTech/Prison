<?php

namespace prison\mysteryboxes\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;
use prison\mysteryboxes\items\KeyNote;

use core\Core;
use core\discord\objects\{
	Post,
	Webhook,
	Embed,
	Field,
	Footer
};
use core\utils\ItemRegistry;
use core\utils\TextFormat;

class ExtractKeys extends Command {

	public function __construct(public Prison $plugin, string $name, string $description) {
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["keynote"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		/** @var PrisonPlayer $sender */
		if (!$sender instanceof Player) return;

		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::RN . "Usage: /extractkeys <type> [amount]");
			return;
		}

		$type = strtolower(array_shift($args));
		$amount = 1;
		if (isset($args[0])) $amount = (int) array_shift($args);

		if ($amount <= 0) {
			$sender->sendMessage(TextFormat::RN . "Amount must be at least 1!");
			return;
		}

		if (!in_array($type, ["iron", "gold", "diamond", "emerald", "vote", "divine"])) {
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

		if ($this->plugin->getMysteryBoxes()->isOpeningBox($sender)) {
			$sender->sendMessage(TextFormat::RI . "You cannot run this command while opening a crate!");
			return;
		}
		$session = $sender->getGameSession()->getMysteryBoxes();
		$keys = $session->getKeys($type);
		if ($keys < $amount) {
			$sender->sendMessage(TextFormat::RN . "You do not have " . $colors[$type] . $amount . " " . ucfirst($type) . " Keys" . TextFormat::GRAY . " to extract!");
			return;
		}

		$note = ItemRegistry::KEY_NOTE();
		$note->setup($sender, $type, $amount);

		if (!$sender->getInventory()->canAddItem($note)) {
			$sender->sendMessage(TextFormat::RN . "Your inventory is full! Please make room before extracting keys!");
			return;
		}

		$before = $session->getKeys($type);
		$session->takeKeys($type, $amount);
		$after = $session->getKeys($type);
		$sender->getInventory()->addItem($note);

		$post = new Post("", "Key Note Log - " . Core::getInstance()->getNetwork()->getIdentifier(), "[REDACTED]", false, "", [
			new Embed("", "rich", "**" . $sender->getName() . "** just created a Key Note worth **" . number_format($amount) . " " . ucfirst($type) . " keys**", "", "ffb106", new Footer("Joe | " . date("F j, Y, g:ia", time())), "", "[REDACTED]", null, [
				new Field("Before", number_format($before), true),
				new Field("After", number_format($after), true),
			])
		]);
		$post->setWebhook(Webhook::getWebhookByName("keynotes-prison"));
		$post->send();

		$sender->sendMessage(TextFormat::GN . "Successfully extracted " . $colors[$type] . $amount . " " . ucfirst($type) . " Keys!");
	}

	public function getPlugin(): Plugin {
		return $this->plugin;
	}
}
