<?php namespace prison\enchantments\uis\guide;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\enchantments\EnchantmentData;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use core\utils\TextFormat;

class GuideSelectUi extends SimpleForm{

	public $guides = [];

	public function __construct(Player $player, int $rarity) {
		/** @var PrisonPlayer $player */
		$e = Prison::getInstance()->getEnchantments()->getEnchantments($rarity, $player->isStaff());
		$e = array_shift($e);

		parent::__construct($e->getRarityName() . " enchantments", "Select an enchantment below to get it's description!");

		$key = 0;
		foreach(Prison::getInstance()->getEnchantments()->getEnchantments($rarity, $player->isStaff()) as $en){
			if ($en->isDisabled()) continue;
			$this->guides[$key] = $en;
			$key++;
			$this->addButton(new Button($en->getRarityColor() . $en->getName() . TextFormat::DARK_GRAY . PHP_EOL . ($en->getTypeName() ?? "Undefined") . "enchantment" . ($en->isObtainable() ? "" : TextFormat::BOLD . TextFormat::RED . " [DISABLED]")));
		}

		$this->addButton(new Button("Go back"));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		foreach($this->guides as $key => $guide){
			if($response == $key){
				$player->showModal(new ShowGuideUi($guide, true));
				return;
			}
		}
		$player->showModal(new EnchantGuideUi($player));
	}

}