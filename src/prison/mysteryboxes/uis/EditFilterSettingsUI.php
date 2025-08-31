<?php

namespace prison\mysteryboxes\uis;

use core\AtPlayer;
use core\rank\Structure;
use core\ui\elements\customForm\Label;
use core\ui\elements\customForm\Toggle;
use core\ui\windows\CustomForm;
use core\utils\TextFormat as TF;
use prison\mysteryboxes\pieces\FilterSetting;
use prison\PrisonPlayer;

class EditFilterSettingsUI extends CustomForm{

	public function __construct(AtPlayer $player){
		/** @var PrisonPlayer $player */

		parent::__construct("Edit Settings");

		$session = $player->getGameSession()->getMysteryBoxes();

		$this->addElement(new Label(TF::GRAY . "Select which rewards you would like to filter!"));
		$this->addElement(new Toggle("Filter Armor", $session->getFilter()->getSetting(FilterSetting::FILTER_ARMOR)->getValue()));
		$this->addElement(new Toggle("Filter Books", $session->getFilter()->getSetting(FilterSetting::FILTER_BOOKS)->getValue()));
		$this->addElement(new Toggle("Filter Building Blocks", $session->getFilter()->getSetting(FilterSetting::FILTER_BUILDING_BLOCKS)->getValue()));
		$this->addElement(new Label(TF::GRAY . "The items considered \"Custom Items\" include: " . TF::RED . "Mine Nukes, Haste Bombs, Essence, Name Tags, etc..."));
		$this->addElement(new Toggle("Filter Custom Items", $session->getFilter()->getSetting(FilterSetting::FILTER_CUSTOM_ITEMS)->getValue()));
		$this->addElement(new Toggle("Filter Decoration", $session->getFilter()->getSetting(FilterSetting::FILTER_DECORATION)->getValue()));
		$this->addElement(new Label(TF::LIGHT_PURPLE . "Enchanted Golden Apples" . TF::GRAY . " are filtered out with the food setting."));
		$this->addElement(new Toggle("Filter Food", $session->getFilter()->getSetting(FilterSetting::FILTER_FOOD)->getValue()));
		$this->addElement(new Toggle("Filter Miscellaneous", $session->getFilter()->getSetting(FilterSetting::FILTER_MISCELLANEOUS)->getValue()));
		$this->addElement(new Toggle("Filter Ores", $session->getFilter()->getSetting(FilterSetting::FILTER_ORES)->getValue()));
		$this->addElement(new Toggle("Filter Tools", $session->getFilter()->getSetting(FilterSetting::FILTER_TOOLS)->getValue()));

		if($player->isTier3()){
			$this->addElement(new Label(TF::GRAY . "For Testing Purposes:"));
			$this->addElement(new Toggle("Auto Clear", $session->getFilter()->isAutoClearing()));
		}
	}

	public function handle($response, AtPlayer $player){
		/** @var PrisonPlayer $player */
		$session = $player->getGameSession()->getMysteryBoxes();

		$session->getFilter(true)->getSetting(FilterSetting::FILTER_ARMOR)->setValue($response[1]);
		$session->getFilter(true)->getSetting(FilterSetting::FILTER_BOOKS)->setValue($response[2]);
		$session->getFilter(true)->getSetting(FilterSetting::FILTER_BUILDING_BLOCKS)->setValue($response[3]);
		$session->getFilter(true)->getSetting(FilterSetting::FILTER_CUSTOM_ITEMS)->setValue($response[5]);
		$session->getFilter(true)->getSetting(FilterSetting::FILTER_DECORATION)->setValue($response[6]);
		$session->getFilter(true)->getSetting(FilterSetting::FILTER_FOOD)->setValue($response[8]);
		$session->getFilter(true)->getSetting(FilterSetting::FILTER_MISCELLANEOUS)->setValue($response[9]);
		$session->getFilter(true)->getSetting(FilterSetting::FILTER_ORES)->setValue($response[10]);
		$session->getFilter(true)->getSetting(FilterSetting::FILTER_TOOLS)->setValue($response[11]);

		if(Structure::RANK_HIERARCHY[$player->getRank()] < 6){
			$session->getFilter()->setAutoClear($response[13] ?? false); // Does not save
		}

		$player->showModal(new OpenBoxFilterUI($player, TF::GREEN . "Updated Filter Settings\n\n"));
	}
}