<?php namespace prison\enchants\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\plugin\Plugin;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\enchantments\{
	ItemData,
	effects\EffectClass,
	effects\items\EffectItem
};

use core\utils\TextFormat;

class AnimatorTest extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name, $description);
		$this->setPermission("prison.tier3");
		$this->setAliases(["at"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		/** @var PrisonPlayer $sender */
		if(!$sender instanceof Player || !$sender->isTier3()){
			$sender->sendMessage(TextFormat::RI . "no");
			return false;
		}
		$item = $sender->getInventory()->getItemInHand();
		if(!$item instanceof EffectItem){
			$data = new ItemData($item);
			if(!$data->hasEffect()){
				$sender->sendMessage(TextFormat::RI . "Please use this command on an Animator or an item with an animation!");
				return false;
			}
			$id = $data->getEffectId();
			$effect = $data->getEffect();
			$sender->sendMessage(TextFormat::RI . "This item has effect ID " . TextFormat::YELLOW . $id . ($effect instanceof EffectClass ? " (Name: " . $effect->getName() . ")" : " (NO ANIMATION FOUND!)"));
		}else{
			$id = $item->getEffectId();
			$effect = $item->getEffect();
			$sender->sendMessage(TextFormat::RI . "This item has effect ID " . TextFormat::YELLOW . $id . ($effect instanceof EffectClass ? " (Name: " . $effect->getName() . ")" : " (NO ANIMATION FOUND!)"));
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}