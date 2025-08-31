<?php namespace prison\enchantments\uis\blacksmith;

use pocketmine\player\Player;
use pocketmine\item\{
	Pickaxe,
	Sword,
	Tool
};

use prison\PrisonPlayer;
use prison\enchantments\effects\items\EffectItem;
use prison\enchantments\EnchantmentData;

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Dropdown
};

use core\utils\TextFormat;

class AnimatorUi extends CustomForm{

	public $items = [];
	public $animations = [];

	public function __construct(Player $player) {
		/** @var PrisonPlayer $player */
		parent::__construct("Add animation");

		$this->addElement(new Label("Which item are you trying to edit?"));

		$dropdown = new Dropdown("Item selection");
		$key = 0;
		foreach($player->getInventory()->getContents() as $item){
			if($item instanceof Tool || $item instanceof Sword){
				$this->items[$key] = $item;
				$dropdown->addOption($item->getName() . TextFormat::RESET . TextFormat::WHITE . " (" . $item->getDamage() . " uses)");
				$key++;
			}
		}
		if(empty($this->items)){
			$dropdown->addOption("You have no tools or swords!");
		}
		$this->addElement($dropdown);

		$this->addElement(new Label("Select what animation you would like to apply to this item"));

		$dropdown = new Dropdown("Animation selection");
		$key = 0;
		foreach($player->getInventory()->getContents() as $item){
			if($item instanceof EffectItem && $item->getEffectId() != 0){
				$this->animations[$key] = $item;
				$effect = $item->getEffect();
				$dropdown->addOption(EnchantmentData::rarityColor($item->getRarity()) . $effect->getName() . TextFormat::RESET . TextFormat::AQUA . " (" . $effect->getTypeName() . ")");
				$key++;
			}
		}
		if(empty($this->animations)){
			$dropdown->addOption("You have no Animators!");
		}
		$this->addElement($dropdown);
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if(empty($this->items) || empty($this->animations)){
			return;
		}
		$item = $this->items[$response[1]];
		$animator = $this->animations[$response[3]];
		$effect = $animator->getEffect();
		if($effect->getType() == 0 && !$item instanceof Sword){
			$player->sendMessage(TextFormat::RI . "This Animator can only be applied to swords!");
			return;
		}
		if($effect->getType() == 1 && !$item instanceof Pickaxe){
			$player->sendMessage(TextFormat::RI . "This Animator can only be applied to mining tools!");
			return;
		}

		$player->showModal(new AnimateConfirmUi($item, $animator));
	}

}