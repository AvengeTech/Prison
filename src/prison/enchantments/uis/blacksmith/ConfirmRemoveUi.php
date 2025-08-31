<?php namespace prison\enchantments\uis\blacksmith;

use pocketmine\item\{
	Durable,
};
use pocketmine\player\Player;

use prison\PrisonPlayer;
use prison\enchantments\ItemData;
use prison\enchantments\type\Enchantment;

use core\ui\windows\ModalWindow;
use core\utils\ItemRegistry;
use core\utils\TextFormat;
use prison\item\UnboundTome;

class ConfirmRemoveUi extends ModalWindow{

	public function __construct(public Durable $item, public UnboundTome $remover, public Enchantment $ench){
		parent::__construct(
			"Confirm Remove",
			"Are you sure you want to remove the " . $ench->getName() . " enchantment from your " . $item->getVanillaName() . "?" . PHP_EOL . PHP_EOL .
			"Your unbound tome has a " . TextFormat::AQUA . $remover->getReturnChance() . " percent chance" . TextFormat::WHITE . " of returning the enchantment as a book, and will cost you " . TextFormat::YELLOW . $remover->getCost() . " XP Levels" . TextFormat::WHITE . " to use.",
			"Remove enchantment",
			"Go back"
		);
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$item = $this->item;
		$islot = $player->getInventory()->first($item, true);
		if($islot === -1){
			$player->sendMessage(TextFormat::RI . "This tool is no longer in your inventory!");
			return;
		}

		$remover = $this->remover;
		$rslot = $player->getInventory()->first($remover, true);
		if($rslot === -1){
			$player->sendMessage(TextFormat::RI . "This unbound tome is no longer in your inventory!");
			return;
		}
		if($remover->getCost() > $player->getXpManager()->getXpLevel()){
			$player->sendMessage(TextFormat::RI . "You don't have enough XP levels to use this unbound tome!");
			return;
		}
		
		if($response){
			$ench = $this->ench;
			$book = $ench->asBook();
			if(!$player->getInventory()->canAddItem($book)){
				$player->sendMessage(TextFormat::RI . "You must have at least 1 free slot incase your unbound tome returns a book!");
				return;
			}

			$player->getXpManager()->subtractXpLevels($remover->getCost());

			$player->sendMessage(TextFormat::GI . "Successfully removed enchantment from your tool!");

			$item->removeEnchantment($this->ench->getEnchantment());
			if(mt_rand(1, 100) <= $remover->getReturnChance()){
				$player->getInventory()->addItem($this->ench->asBook(true)->setChance(100));
				$player->sendMessage(TextFormat::GI . "Wow! Your unbound tome returned an enchantment book!");
			}

			$remover->pop();

			$data = new ItemData($item);
			$item->setLore($data->calculateLores());

			$player->getInventory()->setItem($islot, $item);
			$player->getInventory()->setItem($rslot, $remover);
		}else{
			$player->showModal(new SelectEnchantmentUi($item, $remover));
		}
	}

}