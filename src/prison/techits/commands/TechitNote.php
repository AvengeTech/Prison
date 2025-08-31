<?php

namespace prison\techits\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;
use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\techits\item\TechitNote as TechitNoteItem;

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

class TechitNote extends Command {

	public $plugin;

	public function __construct(Prison $plugin, $name, $description) {
		$this->plugin = $plugin;
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		/** @var PrisonPlayer $sender */
		if ($sender instanceof Player) {
			$amount = (int)array_shift($args);

			if ($amount <= 0) {
				$sender->sendMessage(TextFormat::RN . "Amount must be at least 1!");
				return false;
			}

			if ($amount > $sender->getTechits()) {
				$sender->sendMessage(TextFormat::RN . "You do not have enough Techits!");
				return false;
			}

			$item = ItemRegistry::TECHIT_NOTE();
			$item->setup($sender, $amount);
			if (!$sender->getInventory()->canAddItem($item)) {
				$sender->sendMessage(TextFormat::RN . "Your inventory is full!");
				return false;
			}

			$sender->getInventory()->addItem($item);
			$before = $sender->getTechits();
			$sender->takeTechits($amount);
			$after = $sender->getTechits();
			$sender->sendMessage(TextFormat::GN . "Techit Note added to your inventory!");

			$post = new Post("", "Pay Log - " . Core::getInstance()->getNetwork()->getIdentifier(), "[REDACTED]", false, "", [
				new Embed("", "rich", "**" . $sender->getName() . "** just created a Techit Note worth **" . number_format($amount) . " techits**", "", "ffb106", new Footer("Joe | " . date("F j, Y, g:ia", time())), "", "[REDACTED]", null, [
					new Field("Before", number_format($before), true),
					new Field("After", number_format($after), true),
				])
			]);
			$post->setWebhook(Webhook::getWebhookByName("prison-paylog"));
			$post->send();

			return true;
		}
	}

	public function getPlugin(): Plugin {
		return $this->plugin;
	}
}
