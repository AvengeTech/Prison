<?php namespace prison\enchantments\uis\aguide;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use core\utils\TextFormat;

class GuideSelectUi extends SimpleForm{

	public $guides = [];

	public function __construct(Player $player, int $rarity) {
		/** @var PrisonPlayer $player */
		$e = Prison::getInstance()->getEnchantments()->getEffects()->getEffects($rarity, $player->isStaff());
		$e = array_shift($e);

		parent::__construct($e->getRarityName() . " animators", "Select an animator below to get it's description!");

		foreach(Prison::getInstance()->getEnchantments()->getEffects()->getEffects($rarity, $player->isStaff()) as $eff){
			$this->guides[] = $eff;
			$this->addButton(new Button($eff->getRarityColor() . $eff->getName() . TextFormat::DARK_GRAY . PHP_EOL . ($eff->getType() == 0 ? "Death" : "Mining") . " animation" . ($eff->isObtainable() ? "" : TextFormat::BOLD . TextFormat::RED . " [DISABLED]")));
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
		$player->showModal(new AnimatorGuideUi($player));
	}

}