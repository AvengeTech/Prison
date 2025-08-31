<?php namespace prison\settings\ui;

use pocketmine\player\Player;

use prison\settings\PrisonSettings;
use prison\PrisonPlayer;

use core\settings\ui\SettingsUi;
use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Toggle
};
use core\utils\TextFormat;

class PrisonSettingsUi extends CustomForm{

	public function __construct(Player $player){
		/** @var PrisonPlayer $player */
		parent::__construct("Prison settings");

		$settings = $player->getGameSession()->getSettings()->getSettings();
		$this->addElement(new Label("Free settings"));
		$this->addElement(new Toggle("Rainbow boss bar", $settings[PrisonSettings::RAINBOW_BOSS_BAR]));
		$this->addElement(new Toggle("No tool drop", $settings[PrisonSettings::NO_TOOL_DROP]));
		$this->addElement(new Toggle("Tool break alert", $settings[PrisonSettings::TOOL_BREAK_ALERT]));
		$this->addElement(new Toggle("Lightning strikes", $settings[PrisonSettings::LIGHTNING]));

		$this->addElement(new Label("Premium settings"));
		$this->addElement(new Label("(Each premium setting is marked with the lowest rank it's compatible with)"));
		$this->addElement(new Toggle(TextFormat::ICON_ENDERMAN . " Your MysteryBox alerts", $settings[PrisonSettings::MY_MYSTERY_BOX_MESSAGES]));
		$this->addElement(new Toggle(TextFormat::ICON_ENDERMAN . " Other MysteryBox alerts", $settings[PrisonSettings::OTHER_MYSTERY_BOX_MESSAGES]));
		$this->addElement(new Toggle(TextFormat::ICON_WITHER . " Auto sell", $settings[PrisonSettings::AUTOSELL]));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$session = $player->getGameSession()->getSettings();

		$session->setSetting(PrisonSettings::RAINBOW_BOSS_BAR, $response[1]);
		$session->setSetting(PrisonSettings::NO_TOOL_DROP, $response[2]);
		$session->setSetting(PrisonSettings::TOOL_BREAK_ALERT, $response[3]);
		$session->setSetting(PrisonSettings::LIGHTNING, $response[4]);

		if($player->rankAtLeast("enderman")) $session->setSetting(PrisonSettings::MY_MYSTERY_BOX_MESSAGES, $response[7]);
		if($player->rankAtLeast("enderman")) $session->setSetting(PrisonSettings::OTHER_MYSTERY_BOX_MESSAGES, $response[8]);
		if($player->rankAtLeast("wither")) $session->setSetting(PrisonSettings::AUTOSELL, $response[9]);

		$player->showModal(new SettingsUi(TextFormat::EMOJI_CHECKMARK . TextFormat::GREEN . " Prison settings have been updated!"));
	}

}