<?php namespace prison\enchantments\effects\commands;

use core\utils\ItemRegistry;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;
use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\enchantments\effects\items\EffectItem;

use core\utils\TextFormat;

class GiveAnimator extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name,$description);
		$this->setPermission("prison.tier3");
		$this->setAliases(["ga"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player){
			if(!$sender->isTier3()){
				$sender->sendMessage(TextFormat::RN . "You do not have permission to use this command");
				return false;
			}

			if(count($args) < 1){
				$sender->sendMessage(TextFormat::RN . "Usage: /giveanimator <id:name> [cost]");
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

			$item = ItemRegistry::EFFECT_ITEM();
			$item->setup($effect->getRarity(), $effect, (int)(array_shift($args) ?? -1));
			$sender->getInventory()->addItem($item);

			$sender->sendMessage(TextFormat::GI . "Gave yourself '" . TextFormat::YELLOW . $effect->getName() . TextFormat::GRAY . "' animator");
			return true;
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}