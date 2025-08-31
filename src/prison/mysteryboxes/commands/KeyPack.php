<?php

namespace prison\mysteryboxes\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;
use prison\PrisonSession;

use core\Core;
use core\discord\objects\{
	Post,
	Webhook,
	Embed,
	Field,
	Footer
};
use core\user\User;
use core\utils\TextFormat;

class KeyPack extends Command {

	const PACKS = [
		"small" => [
			"iron" => 50,
			"gold" => 50,
			"diamond" => 50,
			"emerald" => 50,
		],
		"medium" => [
			"iron" => 125,
			"gold" => 125,
			"diamond" => 100,
			"emerald" => 75,
		],
		"large" => [
			"iron" => 250,
			"gold" => 250,
			"diamond" => 200,
			"emerald" => 150,
		],
		"extra-large" => [
			"iron" => 750,
			"gold" => 600,
			"diamond" => 450,
			"emerald" => 350,
			"divine" => 3
		],

	];

	public function __construct(public Prison $plugin, string $name, string $description) {
		parent::__construct($name, $description);
		$this->setPermission("prison.tier3");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args): void {
		if ($sender instanceof Player) {
			/** @var PrisonPlayer $sender */
			if ($sender->getRank() != "owner" && !$sender->isTier3() && !Prison::getInstance()->isTestServer()) {
				$sender->sendMessage(TextFormat::RN . "You do not have permission to use this command");
				return;
			}
		}

		if (count($args) != 2) {
			$sender->sendMessage(TextFormat::RN . "Usage: /keypack <player> <type>");
			return;
		}

		$name = array_shift($args);
		$type = strtolower(array_shift($args));

		$player = $this->plugin->getServer()->getPlayerExact($name);
		if ($player instanceof Player) {
			$name = $player->getName();
		}

		if (!in_array($type, ["small", "medium", "large", "extra-large"])) {
			$sender->sendMessage(TextFormat::RN . "Invalid key pack type! (small, medium, large, extra large)");
			return;
		}

		$keys = self::PACKS[$type];

		Core::getInstance()->getUserPool()->useUser($name, function (User $user) use ($sender, $player, $type, $keys): void {
			if ($sender instanceof Player && !$sender->isConnected()) return;
			if (!$user->valid()) {
				$sender->sendMessage(TextFormat::RI . "Player never seen!");
				return;
			}
			Prison::getInstance()->getSessionManager()->useSession($user, function (PrisonSession $session) use ($sender, $player, $type, $keys): void {
				if ($sender instanceof Player && !$sender->isConnected()) return;
				foreach ($keys as $t => $amount)
					$session->getMysteryBoxes()->addKeys($t, $amount);
				if ($player instanceof Player && $player->isConnected()) {
					$player->sendMessage(TextFormat::GI . "You have received a " . TextFormat::YELLOW . $type . " key pack!");
				} else {
					$session->getMysteryBoxes()->saveAsync();
				}
				$sender->sendMessage(TextFormat::GN . "Successfully gave a " . $type . " key pack to " . $session->getUser()->getGamertag() . "!");
				$post = new Post("", "Key Log - " . Core::getInstance()->getNetwork()->getIdentifier(), "[REDACTED]", false, "", [
					new Embed("", "rich", "**" . $sender->getName() . "** just gave a " . $type . " key pack to " . $session->getUser()->getGamertag() . "!", "", "ffb106", new Footer("ok | " . date("F j, Y, g:ia", time())), "", "[REDACTED]", null, [])
				]);
				$post->setWebhook(Webhook::getWebhookByName("keynotes-prison"));
				$post->send();
			});
		});
	}

	public function getPlugin(): Plugin {
		return $this->plugin;
	}
}
