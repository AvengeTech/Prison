<?php namespace prison\enchantments\uis\tree;

use pocketmine\item\{
	Sword,
	Pickaxe
};
use pocketmine\player\Player;

use prison\enchantments\ItemData;
//use skyblock\fishing\item\FishingRod;

use core\ui\windows\ModalWindow;
use core\utils\TextFormat;
use prison\PrisonPlayer;

class PrestigeToolUi extends ModalWindow{

	public function __construct(Player $player, public Pickaxe|Sword $item){
		$data = new ItemData($item);
		$enchs = $data->getEnchantments();
		$total = 0;
		$overclocks = 0;
		foreach($enchs as $ench){
			if($ench->getStoredLevel() >= $ench->getMaxLevel()){
				if(
					$ench->canOverclock() &&
					$ench->getStoredLevel() < $ench->getMaxLevel() + 1
				){
					$total++;
					$overclocks++;
				}
			}else{
				$total++;
			}
		}
		parent::__construct(
			"Prestige tool?",
			"Are you sure you would like to prestige this tool for " . TextFormat::AQUA . number_format($data->getPrestigeCost()) . TextFormat::WHITE . " techits?" . PHP_EOL . PHP_EOL .
			"When you prestige your tool, you earn " . TextFormat::RED . "2 divine keys" . TextFormat::WHITE . ", and there's a " . TextFormat::YELLOW . "50%% chance" . TextFormat::WHITE . " a random enchantment will be leveled up. However, your tool's level and XP will be reset, along with all of your skill points and skill trees." . PHP_EOL . PHP_EOL .
			"Your tool has " . TextFormat::GREEN . $total . TextFormat::WHITE . " total enchantments that can be leveled up (" . TextFormat::YELLOW . $overclocks . TextFormat::WHITE . " can be overclocked)",
			"Prestige tool",
			"Go back"
		);
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$slot = $player->getInventory()->first($this->item, true);
		if($slot === -1){
			$player->sendMessage(TextFormat::RI . "This item is no longer in your inventory!");
			return;
		}
		$data = new ItemData($item = $player->getInventory()->getItem($slot));

		if($response){
			if($player->getTechits() < $data->getPrestigeCost()){
				$player->showModal(new SkillTreeUi($player, $item, "You don't have enough techits to prestige your tool!"));
				return;
			}
			$ench = $data->prestige($player);
			$player->sendMessage(TextFormat::GI . "Your tool has been prestiged! You earned " . TextFormat::RED . "2 divine keys");
			if($ench !== null){
				$overclocked = $ench->canOverclock() && $ench->getStoredLevel() > $ench->getMaxLevel();
				$player->sendMessage(TextFormat::GI . "One of your tool enchantments was also " . ($overclocked ? TextFormat::RED . "overclocked" : TextFormat::YELLOW . "upgraded" ) . TextFormat::GRAY . " (" . $ench->getRarityColor() . $ench->getName() . TextFormat::GRAY . ")");
			}
			$player->getInventory()->setItem($slot, $data->getItem());
		}else{
			$player->showModal(new SkillTreeUi($player, $item));
		}
	}
}