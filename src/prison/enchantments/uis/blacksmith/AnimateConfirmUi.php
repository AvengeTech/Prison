<?php namespace prison\enchantments\uis\blacksmith;

use pocketmine\player\Player;
use pocketmine\item\Item;

use prison\PrisonPlayer;
use prison\enchantments\effects\items\EffectItem;
use prison\enchantments\ItemData;

use core\ui\windows\ModalWindow;

use core\utils\TextFormat;
class AnimateConfirmUi extends ModalWindow{

	public $item;
	public $animator;

	public function __construct(Item $item, EffectItem $animator){
		$this->item = $item;
		$this->animator = $animator;

		parent::__construct("Confirm Animation", "It will cost you " . TextFormat::YELLOW . $animator->getApplyCost() . " XP Levels " . TextFormat::WHITE . "to apply this animation. Are you sure you want to continue?", "Apply Animator", "Cancel");
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if($response){
			$item = $this->item;
			$animator = $this->animator;
			$slot1 = $player->getInventory()->first($item, true);
			$slot2 = $player->getInventory()->first($animator, true);

			if($slot1 == -1 || $slot2 == -1){
				$player->sendMessage(TextFormat::RN . "One of the items you're trying to use is no longer in your inventory.");
				return;
			}
			if($player->getXpManager()->getXpLevel() < ($cost = $animator->getApplyCost())){
				$player->sendMessage(TextFormat::RN . "You do not have enough XP to animate this item!");
				return;
			}

			$data = new ItemData($player->getInventory()->getItem($slot1));
			$data->setEffectId(($effect = $animator->getEffect())->getId());
			$item = $data->getItem();

			$player->getInventory()->setItem($slot1, $item);
			$player->getInventory()->removeItem($animator);
			$player->getXpManager()->subtractXpLevels($cost);
			$player->sendMessage(TextFormat::GI . "Successfully added '" . TextFormat::YELLOW . $effect->getName() . TextFormat::GRAY . "' animation to your item!");
		}else{
			$player->showModal(new AnimatorUi($player));
		}
	}

}