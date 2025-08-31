<?php

namespace prison\commands;

use core\AtPlayer;
use core\command\type\CoreCommand;
use core\rank\Rank;
use core\utils\ItemRegistry;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use pocketmine\player\Player;
use prison\PrisonPlayer;
use core\utils\TextFormat;
use prison\enchantments\book\RedeemableBook;
use prison\enchantments\EnchantmentData;
use prison\Prison;

class GiveSpecial extends CoreCommand {

	public function __construct(public Prison $plugin, string $name, string $description) {
		parent::__construct($name, $description);
		$this->setHierarchy(Rank::HIERARCHY_HEAD_MOD);
		$this->setInGameOnly();
	}

	/**
	 * @param PrisonPlayer $sender
	 */
	public function handlePlayer(AtPlayer $sender, string $commandLabel, array $args)
	{
		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::RN . "Usage: /givespecial <nt|cdt|ut|rb|mb|sb|mn|hb> [extra args]");
			return;
		}
		$type = array_shift($args);
		switch ($type) {
			case "nametag":
			case "nt":
				$item = ItemRegistry::NAMETAG();
				$item->init();
				$sender->getInventory()->addItem($item);
				$sender->sendMessage(TextFormat::GI . "Given nametag!");
				break;
			case "cdt":
				$item = ItemRegistry::CUSTOM_DEATH_TAG();
				$item->init();
				$sender->getInventory()->addItem($item);
				$sender->sendMessage(TextFormat::GI . "Given custom death tag!");
				break;
			case "ut":
				$item = ItemRegistry::UNBOUND_TOME();
				$item->init((int) (array_shift($args) ?? -1));
				$sender->getInventory()->addItem($item);
				$sender->sendMessage(TextFormat::GI . "Given unbound tome!");
				break;
			case "rb":
				$item = ItemRegistry::REDEEMABLE_BOOK();
				$item->setup(RedeemableBook::TYPE_RANDOM_RARITY, -1, true);
				$item->init();
				$item->setCount((int) (array_shift($args) ?? 1));

				$sender->getInventory()->addItem($item);
				$sender->sendMessage(TextFormat::GI . "Given random book!");
				break;
			case "mb":
				$item = ItemRegistry::REDEEMABLE_BOOK();
				$item->setup(RedeemableBook::TYPE_MAX_RANDOM_RARITY, -1, true);
				$item->init();
				$item->setCount((int) (array_shift($args) ?? 1));

				$sender->getInventory()->addItem($item);
				$sender->sendMessage(TextFormat::GI . "Given max book!");
				break;
			case "sb":
				$item = ItemRegistry::SALE_BOOSTER();
				$item->setup((float) (array_shift($args) ?? (mt_rand(3, 4) / 2)), (int) (array_shift($args) ?? 900));
				$item->setCount((int) (array_shift($args) ?? 1));

				$sender->getInventory()->addItem($item);
				$sender->sendMessage(TextFormat::GI . 'Given sale booster!');
				break;
			case "mn":
				$item = ItemRegistry::MINE_NUKE();
				$item->init();
				$item->setCount((int) array_shift($args) ?? 1);

				$sender->getInventory()->addItem($item);
				$sender->sendMessage(TextFormat::GI . "Given mine nuke!");
				break;
			case "hb":
				$item = ItemRegistry::HASTE_BOMB();
				$item->init();
				$item->setCount((int) array_shift($args) ?? 1);

				$sender->getInventory()->addItem($item);
				$sender->sendMessage(TextFormat::GI . "Given haste bomb!");
				break;
			case "eos":
				$item = ItemRegistry::ESSENCE_OF_SUCCESS()->setup(
					(int)(count($args) == 0 ? EnchantmentData::RARITY_COMMON : array_shift($args)),
					(int)(count($args) == 0 ? -1 : array_shift($args)),
					(int)(count($args) == 0 ? -1 : array_shift($args)),
					(int)(count($args) == 0 ? false : array_shift($args))
				);
				$item->init();
				$item->setCount((int)(count($args) == 0 ? 1 : array_shift($args)));

				$sender->getInventory()->addItem($item);
				$sender->sendMessage(TextFormat::GI . "Given Essence of Success!");
				break;
			case "eok":
				$item = ItemRegistry::ESSENCE_OF_KNOWLEDGE()->setup(
					(int)(count($args) == 0 ? EnchantmentData::RARITY_COMMON : array_shift($args)),
					(int)(count($args) == 0 ? -1 : array_shift($args)),
					(int)(count($args) == 0 ? false : array_shift($args))
				);
				$item->init();
				$item->setCount((int)(count($args) == 0 ? 1 : array_shift($args)));

				$sender->getInventory()->addItem($item);
				$sender->sendMessage(TextFormat::GI . "Given Essence of Knowledge!");
				break;
		}
	}

	public function getPlugin(): Plugin {
		return $this->plugin;
	}
}
