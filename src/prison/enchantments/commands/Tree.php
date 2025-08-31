<?php namespace prison\enchantments\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

use pocketmine\item\{
	Sword,
	Pickaxe
};

use prison\Prison;
use prison\PrisonPlayer;
use prison\enchantments\ItemData;
use prison\enchantments\uis\tree\SkillTreeUi;
//use skyblock\fishing\item\FishingRod;

use core\utils\TextFormat;

class Tree extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		/** @var PrisonPlayer $sender */
		if(!$sender instanceof Player) return;

		$item = $sender->getInventory()->getItemInHand();
		if(
			!$item instanceof Pickaxe &&
			!$item instanceof Sword/* &&
			!$item instanceof FishingRod*/
		){
			$sender->sendMessage(TextFormat::RI . "Only pickaxes, swords and fishing rods have skill trees! Make sure you're holding the correct item");
			return;
		}

		if(count($args) !== 0 && $sender->isTier3()){
			switch(array_shift($args)){
				case "as":
					$data = new ItemData($item);
					$data->addSkillPoint();
					$data->getItem()->setLore($data->calculateLores());
					$data->send($sender);
					$sender->sendMessage(TextFormat::GI . "Added skill point to held item!");
					return;
				case "al":
					$data = new ItemData($item);
					$data->levelUp();
					$data->sendLevelUpTitle($sender);
					$data->send($sender);
					$sender->sendMessage(TextFormat::GI . "Added level to held item!");
					return;
			}
		}
		$sender->showModal(new SkillTreeUi($sender, $item));
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}