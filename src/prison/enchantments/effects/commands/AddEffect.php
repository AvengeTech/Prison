<?php namespace prison\enchantments\effects\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;
use pocketmine\item\{
	Bow,
	Sword
};

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\item\NetheriteSword;
use prison\enchantments\effects\EffectIds;

use core\utils\TextFormat;

class AddEffect extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name,$description);
		$this->setPermission("prison.tier3");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player){
			if(!$sender->isTier3() && !Prison::getInstance()->isTestServer()){
				$sender->sendMessage(TextFormat::RN . "You do not have permission to use this command");
				return false;
			}

			if(count($args) != 1){
				$sender->sendMessage(TextFormat::RN . "Usage: /addeffect <id:name>");
				return false;
			}

			$id = array_shift($args);
			if(is_numeric($id)){
				$effect = Prison::getInstance()->getEnchantments()->getEffects()->getEffectById($id);
			}else{
				$effect = Prison::getInstance()->getEnchantments()->getEffects()->getEffectByName($id);
			}
			if($effect === null){
				$sender->sendMessage(TextFormat::RN . "Invalid effect id!");
				return false;
			}

			$item = $sender->getInventory()->getItemInHand();

			if($item instanceof Bow){
				$sender->sendMessage(TextFormat::RN . "Effects can't be added to bows");
				return false;
			}
			if(($item instanceof Sword) && $effect->getType() == EffectIds::TYPE_TOOL){
				$sender->sendMessage(TextFormat::RN . "Tool effects can't be added to swords");
				return false;
			}
			if((!$item instanceof Sword) && $effect->getType() == EffectIds::TYPE_SWORD){
				$sender->sendMessage(TextFormat::RN . "Sword effects can't be added to tools");
				return false;
			}

			$data = Prison::getInstance()->getEnchantments()->getItemData($item);
			$data->setEffectId($effect->getId());
			$item = $data->getItem();
			$sender->getInventory()->setItemInHand($item);

			$sender->sendMessage(TextFormat::GI . "Item in hand given effect '" . TextFormat::YELLOW . $effect->getName() . TextFormat::GRAY . "'!");
			return true;
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}