<?php namespace prison\enchantments\uis\guide;

use pocketmine\player\Player;

use prison\enchantments\type\{
	Enchantment,
	ArmorEnchantment
};
use prison\enchantments\EnchantmentData;
use prison\PrisonPlayer;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

class ShowGuideUi extends SimpleForm{

	public $enchantment;

	public function __construct(Enchantment $enchantment, bool $back = true){
		$this->enchantment = $enchantment;
		parent::__construct($enchantment->getName(),
			$enchantment->getRarityName() . " " . ($enchantment->getTypeName() ?? "Undefined") . "enchantment" . PHP_EOL . PHP_EOL . "Max level: " . $enchantment->getMaxLevel() . ($enchantment instanceof ArmorEnchantment ? PHP_EOL . ($enchantment->isStackable() ? "Stackable: YES" . PHP_EOL . "Max stack level: " . $enchantment->getMaxStackLevel() : "Stackable: NO") : "") . PHP_EOL . PHP_EOL . "Description: " . $enchantment->getDescription()
		);
		if($back) $this->addButton(new Button("Go back"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$player->showModal(new GuideSelectUi($player, $this->enchantment->getRarity()));
	}

}